<?php

namespace LgtToolkit\Support\Queue;

use LgtToolkit\Support\Delivery\DeliveryAction;

final class RetrySummary
{
    public int $delivered = 0;
    public int $rescheduled = 0;
    public int $failed = 0;
    public int $expired = 0;

    /** Why nothing ran: 'busy', 'unconfigured' or 'non_production'. */
    public ?string $skipped = null;

    /** Set when a systemic error (bad key/endpoint, rate limit) ended the run early. */
    public ?DeliveryAction $stoppedEarly = null;

    /** @var list<QueuedTicket> due items, for --dry-run */
    public array $due = [];

    public function toText(): string
    {
        if ($this->skipped !== null) {
            return match ($this->skipped) {
                'busy' => t('Another retry run is in progress.'),
                'non_production' => t('Support tickets are disabled on this non-production site.'),
                default => t('Support tickets are not configured.'),
            };
        }

        $text = t('Delivered %s, rescheduled %s, failed %s, expired %s.', $this->delivered, $this->rescheduled, $this->failed, $this->expired);
        if ($this->stoppedEarly !== null) {
            $text .= ' ' . t('Stopped early: the helpdesk refused the request (%s).', $this->stoppedEarly->value);
        }

        return $text;
    }
}
