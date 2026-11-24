<?php

namespace LgtToolkit\Providers\Support;

use LgtToolkit\Support\TicketLog;
use LgtToolkit\Support\SupportSettings;
use LgtToolkit\Support\TicketValidator;
use LgtToolkit\Support\UserRateLimiter;
use Concrete\Core\Logging\LoggerFactory;
use LgtToolkit\Support\Queue\QueueService;
use LgtToolkit\Support\Queue\RetryProcessor;
use LgtToolkit\Support\TicketPayloadBuilder;
use LgtToolkit\Support\Delivery\TicketSender;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\System\Mutex\MutexInterface;
use Concrete\Core\Database\Connection\Connection;
use LgtToolkit\Support\Diagnostics\DiagnosticsSource;
use LgtToolkit\Support\Diagnostics\DiagnosticsCollector;
use LgtToolkit\Support\Diagnostics\ConcreteDiagnosticsSource;

/**
 * Registers the support ticket services. Settings come from .env via SupportEnv.
 */
class SupportServiceProvider extends Provider
{
    public const LOG_CHANNEL = 'lgt_support';

    public function register()
    {
        $logger = fn() => $this->app->make(LoggerFactory::class)->createLogger(self::LOG_CHANNEL);

        $this->app->singleton(SupportSettings::class, fn() => new SupportSettings(
            $this->app->make('config')->get('concrete.security.production.mode'),
        ));
        $this->app->alias(SupportSettings::class, 'lgt_support');

        $this->app->singleton(TicketPayloadBuilder::class);
        $this->app->bind(DiagnosticsSource::class, ConcreteDiagnosticsSource::class);
        $this->app->bind(DiagnosticsCollector::class, fn() => new DiagnosticsCollector($this->app->make(DiagnosticsSource::class)));
        $this->app->bind(TicketValidator::class, fn() => new TicketValidator(ConcreteDiagnosticsSource::canonicalUrl($this->app)));

        $this->app->bind(TicketSender::class, fn() => new TicketSender($this->app->make(SupportSettings::class), $logger()));
        $this->app->bind(TicketLog::class, fn() => new TicketLog($this->app->make(Connection::class)));
        $this->app->bind(QueueService::class, fn() => new QueueService($this->app->make(Connection::class), $logger()));
        $this->app->bind(RetryProcessor::class, fn() => new RetryProcessor(
            $this->app->make(QueueService::class),
            $this->app->make(TicketSender::class),
            $this->app->make(TicketPayloadBuilder::class),
            $this->app->make(SupportSettings::class),
            $this->app->make(MutexInterface::class),
            $this->app->make(TicketLog::class),
            $logger(),
        ));

        // shortcut: if the expensive cache is disabled the per-user limit never triggers; the portal's 30/site/hour still applies.
        $this->app->bind(UserRateLimiter::class, function () {
            $cache = $this->app->make('cache/expensive');

            return new UserRateLimiter(
                static function (string $key) use ($cache): int {
                    $item = $cache->getItem($key);

                    return $item->isMiss() ? 0 : (int) $item->get();
                },
                static function (string $key, int $value, int $ttl) use ($cache): void {
                    $item = $cache->getItem($key);
                    $item->set($value);
                    $item->expiresAfter($ttl);
                    $cache->save($item);
                },
            );
        });
    }
}
