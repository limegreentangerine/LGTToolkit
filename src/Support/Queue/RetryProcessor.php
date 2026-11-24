<?php

namespace LgtToolkit\Support\Queue;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use LgtToolkit\Support\SupportSettings;
use LgtToolkit\Support\TicketPayloadBuilder;
use LgtToolkit\Support\Delivery\TicketSender;
use Concrete\Core\System\Mutex\MutexInterface;
use LgtToolkit\Support\Delivery\DeliveryAction;
use LgtToolkit\Support\Delivery\DeliveryResult;
use Concrete\Core\System\Mutex\MutexBusyException;

/**
 * One run of the scheduled lgt_support_retry task: release stale locks, expire, send due items, purge.
 */
class RetryProcessor
{
    public const MUTEX_KEY = 'lgt_support_retry';
    public const BATCH_SIZE = 10;

    public function __construct(
        private readonly QueueService $queue,
        private readonly TicketSender $sender,
        private readonly TicketPayloadBuilder $builder,
        private readonly SupportSettings $settings,
        private readonly MutexInterface $mutex,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    private function process(RetrySummary $summary, int $timeout, bool $dryRun, \DateTimeImmutable $now): void
    {
        if ($dryRun) {
            $summary->due = array_values(array_filter(array_map($this->queue->find(...), $this->queue->due(self::BATCH_SIZE, $now))));

            return;
        }

        $this->queue->releaseStale($now);
        $summary->expired = $this->queue->expire($now);

        foreach ($this->queue->due(self::BATCH_SIZE, $now) as $id) {
            if (!$this->queue->claim($id, $now)) {
                continue;
            }

            $item = $this->queue->find($id);
            if ($item === null) {
                continue;
            }

            $result = $this->deliver($item, $timeout, $now);

            if ($result->isDelivered()) {
                $this->queue->markDelivered($id, $result->ticket, $now);
                $summary->delivered++;
            } elseif ($result->shouldRetry()) {
                $this->queue->reschedule($item, $result, $now);
                $summary->rescheduled++;
            } else {
                $this->queue->markFailed($id, $result->statusCode, $result->error, $now);
                $summary->failed++;
            }

            if ($result->action->stopsBatch()) {
                $summary->stoppedEarly = $result->action;
                break;
            }
        }

        $this->queue->purge($now);
    }

    private function deliver(QueuedTicket $item, int $timeout, \DateTimeImmutable $now): DeliveryResult
    {
        try {
            $message = $this->builder->deliveryNote($item->message, $item->localReference, $now, $item->attempts, $item->createdAt, $item->possibleDuplicate);
            $payload = $this->builder->payload($item->email, $item->name, $item->subject, $message, $item->pageUrl, $this->queue->attachments($item->id));
        } catch (\Throwable $e) {
            // Nothing was sent; stored data that cannot be rebuilt will not fix itself.
            $this->logger->error('Queued support ticket could not be prepared.', ['reference' => $item->localReference, 'error' => $e->getMessage()]);

            return new DeliveryResult(DeliveryAction::PermanentFailure, 0, error: 'The queued ticket could not be prepared.');
        }

        return $this->sender->send($payload, $timeout);
    }

    public function run(
        int $timeout = TicketSender::CRON_TIMEOUT,
        bool $dryRun = false,
        ?\DateTimeImmutable $now = null,
    ): RetrySummary {
        $summary = new RetrySummary();

        if (!$this->settings->isEnabled()) {
            $summary->skipped = $this->settings->disabledReason();
            $this->logger->info('Support ticket retry skipped: ' . $summary->skipped);

            return $summary;
        }

        try {
            $this->mutex->execute(self::MUTEX_KEY, function () use ($summary, $timeout, $dryRun, $now) {
                $this->process($summary, $timeout, $dryRun, $now ?? new \DateTimeImmutable());
            });
        } catch (MutexBusyException) {
            $summary->skipped = 'busy';
        }

        return $summary;
    }
}
