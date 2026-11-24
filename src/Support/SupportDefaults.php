<?php

namespace LgtToolkit\Support;

/**
 * Agency-wide defaults; not configurable per site.
 */
final class SupportDefaults
{
    /**
     * Wrap in t() when rendering.
     */
    public const HOURS_TEXT = 'We respond to support requests 09:00–17:00 on working days. Requests sent outside these hours are picked up the next working day.';

    public const INCLUDE_ERROR_LOG = true;

    /**
     * Per user, best effort; the portal enforces 30/site/hour on its own.
     */
    public const USER_RATE_LIMIT_PER_HOUR = 5;
}
