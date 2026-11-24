<?php

namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use LgtToolkit\Support\SupportEnv;
use LgtToolkit\Support\SupportSettings;
use LgtToolkit\Support\Queue\QueueService;
use LgtToolkit\Support\Queue\RetrySchedule;
use Concrete\Core\Page\Controller\DashboardPageController;

/**
 * Read-only: the support settings live in the site's .env file. There is no live key test because the portal
 * has no validation endpoint and any POST creates a real ticket, so queue health is shown instead.
 */
class Support extends DashboardPageController
{
    public function view()
    {
        $apiKey = SupportEnv::apiKey();
        $url = SupportEnv::url();
        $settings = $this->app->make(SupportSettings::class);

        $this->set('settings', [
            [
                'env' => SupportEnv::API_KEY,
                'label' => t('Site Key'),
                'populated' => $apiKey !== null,
                // A secret: only report that it is set, never any part of it.
                'display' => $apiKey !== null ? str_repeat('•', 8) : '',
                'default' => null,
            ],
            [
                'env' => SupportEnv::URL,
                'label' => t('Portal Address'),
                'populated' => $url !== null,
                'display' => $url ?? '',
                'default' => SupportEnv::DEFAULT_URL,
            ],
            [
                'env' => SupportEnv::ENABLED_NON_PRODUCTION,
                'label' => t('Enabled on Non-production'),
                'populated' => SupportEnv::enabledNonProduction(),
                'display' => t('Yes'),
                'default' => t('No'),
            ],
            [
                'env' => SupportEnv::FALLBACK_EMAIL,
                'label' => t('Fallback Email'),
                'populated' => SupportEnv::fallbackEmail() !== null,
                'display' => SupportEnv::fallbackEmail() ?? '',
                'default' => null,
            ],
        ]);

        $this->set('state', $settings->disabledReason() ?? 'enabled');

        $queue = $this->app->make(QueueService::class);
        $this->set('stats', $queue->stats());
        $this->set('overdue', $queue->oldestOverdue(new \DateTimeImmutable()));
        $this->set('schedules', $this->app->make(RetrySchedule::class)->cronExpressions());
        $this->set('schedulingEnabled', (bool) $this->app->make('config')->get('concrete.jobs.enable_scheduling'));
        $this->set('dateHelper', $this->app->make('date'));
    }
}
