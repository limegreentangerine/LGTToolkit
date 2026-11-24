<?php

namespace LgtToolkit\Tests\Unit;

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use LgtToolkit\Support\TicketLog;
use LgtToolkit\Support\SupportEnv;
use Concrete\Core\Production\Modes;
use GuzzleHttp\Handler\MockHandler;
use LgtToolkit\Support\SupportSettings;
use Concrete\Core\Application\Application;
use LgtToolkit\Support\Queue\QueueService;
use LgtToolkit\Support\Queue\RetryProcessor;
use LgtToolkit\Support\TicketPayloadBuilder;
use LgtToolkit\Support\Delivery\TicketSender;
use Concrete\Core\System\Mutex\MutexInterface;
use LgtToolkit\Support\Delivery\DeliveryAction;
use LgtToolkit\Support\Delivery\DeliveryResult;
use Concrete\Core\System\Mutex\MutexBusyException;

final class RetryProcessorTest extends TestCase
{
    use QueueSchema;

    private Connection $db;

    private QueueService $queue;

    private TicketLog $log;

    private array $history = [];

    private \DateTimeImmutable $now;

    private function processor(array $responses, bool $busy = false, string $mode = Modes::MODE_PRODUCTION): RetryProcessor
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $settings = new SupportSettings($mode);

        $mutex = new class($busy) implements MutexInterface {
            public function __construct(private bool $busy) {}

            public static function isSupported(Application $app)
            {
                return true;
            }

            public function acquire($key) {}

            public function release($key) {}

            public function execute($key, callable $callback)
            {
                if ($this->busy) {
                    throw new MutexBusyException($key);
                }
                $callback();
            }
        };

        return new RetryProcessor($this->queue, new TicketSender($settings, handler: $stack), new TicketPayloadBuilder(), $settings, $mutex, $this->log);
    }

    private function enqueue(string $ref, bool $possibleDuplicate = true, ?\DateTimeImmutable $at = null): string
    {
        $at ??= $this->now->modify('-2 hours');
        $this->log->record($ref, 'broken', 'a@example.com', null, null, 'Broken: form', 'Body', null, TicketLog::STATUS_QUEUED, null, null, null, null, $at);

        return $this->queue->enqueue(
            $ref,
            'a@example.com',
            null,
            'Broken: form',
            'Body',
            null,
            null,
            [],
            new DeliveryResult($possibleDuplicate ? DeliveryAction::Retryable : DeliveryAction::ConfigError, $possibleDuplicate ? 500 : 401),
            $at,
        );
    }

    private function logStatus(string $ref): array
    {
        return $this->db->fetchAssociative('SELECT status, portalTicket, portalReference, portalUrl, lastError FROM LgtSupportTickets WHERE localReference = ?', [$ref]);
    }

    public function testMixedBatch(): void
    {
        $delivered = $this->enqueue('Q-1');
        $retry = $this->enqueue('Q-2');
        $rejected = $this->enqueue('Q-3');

        $summary = $this->processor([
            new Response(201, [], '{"ticket": 1, "reference": "#1", "url": "https://portal.limegreentangerine.co.uk/ticket.php?id=1"}'),
            new Response(503),
            new Response(400, [], '{"errors": ["bad"]}'),
        ])->run(now: $this->now);

        $this->assertSame([1, 1, 1], [$summary->delivered, $summary->rescheduled, $summary->failed]);
        $this->assertNull($this->queue->find($delivered));
        $this->assertSame('pending', $this->queue->find($retry)->status);
        $this->assertSame('failed', $this->queue->find($rejected)->status);

        $first = json_decode((string) $this->history[0]['request']->getBody(), true)['message'];
        $this->assertStringContainsString('(attempt 2)', $first);
        $this->assertStringContainsString('if a ticket with local reference Q-1 already exists', $first);

        $this->assertSame(['status' => 'delivered', 'portalTicket' => 1, 'portalReference' => '#1', 'portalUrl' => 'https://portal.limegreentangerine.co.uk/ticket.php?id=1', 'lastError' => null], $this->logStatus('Q-1'));
        $this->assertSame('queued', $this->logStatus('Q-2')['status']);
        $this->assertSame(['failed', 'bad'], [$this->logStatus('Q-3')['status'], $this->logStatus('Q-3')['lastError']]);
    }

    public function testExpiredTicketsAreMarkedFailedInTheLog(): void
    {
        $this->enqueue('Q-1', at: $this->now->modify('-73 hours'));

        $summary = $this->processor([])->run(now: $this->now);

        $this->assertSame(1, $summary->expired);
        $this->assertSame('failed', $this->logStatus('Q-1')['status']);
    }

    public function testLogRecordsOlderThan12MonthsArePurged(): void
    {
        $this->log->record('Q-OLD', 'broken', 'a@example.com', null, null, 'S', 'M', null, TicketLog::STATUS_DELIVERED, 1, '#1', null, null, $this->now->modify('-13 months'));
        $this->log->record('Q-NEW', 'broken', 'a@example.com', null, null, 'S', 'M', null, TicketLog::STATUS_DELIVERED, 2, '#2', null, null, $this->now->modify('-11 months'));

        $this->processor([])->run(now: $this->now);

        $this->assertSame(['Q-NEW'], $this->db->fetchFirstColumn('SELECT localReference FROM LgtSupportTickets'));
    }

    public function testStopsEarlyOnConfigError(): void
    {
        $this->enqueue('Q-1');
        $untouched = $this->enqueue('Q-2');

        $summary = $this->processor([new Response(401), new Response(201)])->run(now: $this->now);

        $this->assertSame(DeliveryAction::ConfigError, $summary->stoppedEarly);
        $this->assertCount(1, $this->history);
        $item = $this->queue->find($untouched);
        $this->assertSame('pending', $item->status);
        $this->assertSame(1, $item->attempts, 'not claimed');
    }

    public function testStopsEarlyOnRateLimit(): void
    {
        $this->enqueue('Q-1');
        $this->enqueue('Q-2');

        $summary = $this->processor([new Response(429, ['Retry-After' => '60']), new Response(201)])->run(now: $this->now);

        $this->assertSame(DeliveryAction::RateLimited, $summary->stoppedEarly);
        $this->assertCount(1, $this->history);
    }

    public function testBusyMutexIsNoOp(): void
    {
        $id = $this->enqueue('Q-1');

        $summary = $this->processor([new Response(201)], busy: true)->run(now: $this->now);

        $this->assertSame('busy', $summary->skipped);
        $this->assertCount(0, $this->history);
        $this->assertSame('pending', $this->queue->find($id)->status);
    }

    public function testDisabledOnNonProductionSendsNothing(): void
    {
        $this->enqueue('Q-1');

        $summary = $this->processor([new Response(201)], mode: Modes::MODE_STAGING)->run(now: $this->now);

        $this->assertSame('non_production', $summary->skipped);
        $this->assertCount(0, $this->history);
    }

    public function testDryRunListsWithoutSending(): void
    {
        $id = $this->enqueue('Q-1');

        $summary = $this->processor([new Response(201)])->run(dryRun: true, now: $this->now);

        $this->assertSame('Q-1', $summary->due[0]->localReference);
        $this->assertCount(0, $this->history);
        $this->assertSame(1, $this->queue->find($id)->attempts);
    }

    public function testDeliveredItemIsNeverResent(): void
    {
        $this->enqueue('Q-1');
        $processor = $this->processor([new Response(201, [], '{"ticket": 1}'), new Response(201)]);

        $processor->run(now: $this->now);
        $processor->run(now: $this->now->modify('+1 day'));

        $this->assertCount(1, $this->history);
    }

    protected function setUp(): void
    {
        $_ENV[SupportEnv::API_KEY] = 'key';
        $this->db = $this->queueDatabase();
        $this->queue = new QueueService($this->db);
        $this->log = new TicketLog($this->db);
        $this->history = [];
        $this->now = new \DateTimeImmutable('2026-11-24 12:00:00');
    }

    protected function tearDown(): void
    {
        unset($_ENV[SupportEnv::API_KEY], $_ENV[SupportEnv::ENABLED_NON_PRODUCTION]);
    }
}
