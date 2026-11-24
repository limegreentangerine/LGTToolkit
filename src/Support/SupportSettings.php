<?php

namespace LgtToolkit\Support;

use Concrete\Core\Production\Modes;

/**
 * Injectable view of SupportEnv plus the production-mode rule, so logic classes avoid statics.
 */
class SupportSettings
{
    /**
     * @param ?string $mode concrete.security.production.mode
     */
    public function __construct(private readonly ?string $mode) {}

    public function apiKey(): ?string
    {
        return SupportEnv::apiKey();
    }

    public function url(): string
    {
        return SupportEnv::urlOrDefault();
    }

    public function fallbackEmail(): ?string
    {
        return SupportEnv::fallbackEmail();
    }

    public function isConfigured(): bool
    {
        return SupportEnv::isConfigured();
    }

    public function isProduction(): bool
    {
        return $this->mode === Modes::MODE_PRODUCTION;
    }

    /**
     * Staging is often a copy of live, .env included, so non-production sends nothing unless opted in.
     */
    public function isEnabled(): bool
    {
        return $this->isConfigured() && ($this->isProduction() || SupportEnv::enabledNonProduction());
    }

    /**
     * Why the feature is off ('unconfigured' or 'non_production'), or null when it is enabled.
     */
    public function disabledReason(): ?string
    {
        if (!$this->isConfigured()) {
            return 'unconfigured';
        }

        return $this->isEnabled() ? null : 'non_production';
    }

    /**
     * Subject prefix for non-production tickets, e.g. "[STAGING]".
     */
    public function subjectPrefix(): ?string
    {
        return match ($this->mode) {
            Modes::MODE_PRODUCTION => null,
            Modes::MODE_STAGING => '[STAGING]',
            default => '[DEV]',
        };
    }
}
