<?php

namespace LgtToolkit\Command\Task;

use LgtToolkit\Support\SupportEnv;
use LgtToolkit\Support\Queue\RetryProcessor;
use Concrete\Core\Command\Task\Output\{OutputAwareInterface, OutputAwareTrait};

/**
 * Sends queued support tickets. This task, scheduled under Dashboard > System & Settings > Automation,
 * is the only thing that sends them. Throws only for misconfiguration; items left pending are normal.
 */
class RetrySupportTicketsCommandHandler implements OutputAwareInterface
{
    use OutputAwareTrait;

    public function __invoke(RetrySupportTicketsCommand $command)
    {
        if (SupportEnv::apiKey() === null) {
            throw new \RuntimeException(t('%s not available — check .env loading.', SupportEnv::API_KEY));
        }

        $summary = app(RetryProcessor::class)->run(dryRun: $command->dryRun);

        if ($command->dryRun && $summary->skipped === null) {
            $this->output->write(t('%s item(s) due.', count($summary->due)));
            foreach ($summary->due as $item) {
                $this->output->write(sprintf('%s  attempts %d  %s', $item->localReference, $item->attempts, $item->subject));
            }

            return;
        }

        $this->output->write($summary->toText());
    }
}
