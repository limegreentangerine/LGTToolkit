<?php

namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard;

use Concrete\Core\User\User;
use LgtToolkit\Support\TicketLog;
use LgtToolkit\Support\LocalReference;
use LgtToolkit\Support\TicketCategory;
use LgtToolkit\Support\SupportDefaults;
use LgtToolkit\Support\SupportSettings;
use LgtToolkit\Support\TicketValidator;
use LgtToolkit\Support\UserRateLimiter;
use Concrete\Core\Logging\LoggerFactory;
use LgtToolkit\Support\Queue\QueueService;
use LgtToolkit\Support\Queue\RetrySchedule;
use LgtToolkit\Support\TicketPayloadBuilder;
use LgtToolkit\Support\Delivery\TicketSender;
use LgtToolkit\Support\Delivery\DeliveryResult;
use LgtToolkit\Support\Diagnostics\DiagnosticsSource;
use LgtToolkit\Providers\Support\SupportServiceProvider;
use LgtToolkit\Support\Diagnostics\DiagnosticsCollector;
use Concrete\Core\Page\Controller\DashboardPageController;

/**
 * Lets site users raise a helpdesk ticket. Delivery is attempted immediately; only failures are queued.
 */
class Support extends DashboardPageController
{
    protected SupportSettings $settings;

    /** @var array<string, string> */
    protected array $fieldErrors = [];

    /** @var array<string, mixed> */
    protected array $values = [];

    protected function validateToken(string $action): bool
    {
        if (!$this->request->isPost()) {
            return false;
        }

        if (!$this->token->validate($action)) {
            $this->error->add($this->token->getErrorMessage());

            return false;
        }

        return true;
    }

    /**
     * PHP silently empties $_POST (and so the CSRF token) when the body exceeds post_max_size.
     */
    protected function postTooLarge(): bool
    {
        $limit = TicketValidator::iniBytes((string) ini_get('post_max_size'));
        $length = (int) $this->request->server->get('CONTENT_LENGTH', 0);

        return $this->request->isPost() && $limit > 0 && $length > $limit && $this->request->request->count() === 0;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $defaults = ['email' => '', 'name' => '', 'page_id' => '', 'page_version_id' => '', 'page_url' => '', 'page_label' => ''];

        $user = $this->app->make(User::class);
        $info = $user->isRegistered() ? $user->getUserInfoObject() : null;
        if ($info) {
            $defaults['email'] = (string) $info->getUserEmail();
            $fullName = trim((string) $info->getAttribute('first_name') . ' ' . (string) $info->getAttribute('last_name'));
            $defaults['name'] = $fullName !== '' ? $fullName : (string) $info->getUserName();
        }

        // Page context from the toolbar button; checked for existence and view permission.
        $pageID = (int) $this->request->query->get('page_id');
        if ($pageID > 0) {
            $versionID = (int) $this->request->query->get('page_version_id') ?: null;
            $page = $this->app->make(DiagnosticsSource::class)->page($pageID, $versionID);
            if ($page !== null) {
                $url = (string) \URL::to($page['path'] === '/' ? '/' : $page['path']);
                $defaults['page_id'] = $page['cID'];
                $defaults['page_version_id'] = $versionID ?? '';
                $defaults['page_label'] = $page['name'] . ' (' . $page['path'] . ')';
                $defaults['page_url'] = $this->app->make(TicketValidator::class)->sameHostUrl($url) ?? '';
            }
        }

        return $defaults;
    }

    protected function deliveredText(DeliveryResult $delivery, string $email): string
    {
        $text = $delivery->reference !== null
            ? t('Thanks — your ticket %s has been created. You\'ll receive an acknowledgement email at %s shortly.', $delivery->reference, $email)
            : t('Thanks — your ticket has been created. You\'ll receive an acknowledgement email at %s shortly.', $email);

        if ($delivery->droppedAttachments !== []) {
            $text .= ' ' . t('Your attachments were too large for the helpdesk and were not included: %s.', implode(', ', $delivery->droppedAttachments));
        }

        return $text;
    }

    protected function fallbackText(): string
    {
        $email = $this->settings->fallbackEmail();

        return $email !== null ? t('Please email %s.', $email) : t('Please contact Limegreentangerine directly.');
    }

    protected function logger(): \Psr\Log\LoggerInterface
    {
        return $this->app->make(LoggerFactory::class)->createLogger(SupportServiceProvider::LOG_CHANNEL);
    }

    /**
     * Records the submission for the super user's ticket list. A failure here never affects the ticket itself.
     */
    protected function logTicket(
        string $reference,
        string $category,
        string $email,
        ?string $name,
        ?int $uID,
        string $subject,
        string $message,
        ?string $pageUrl,
        string $status,
        DeliveryResult $delivery,
        ?string $error,
        \DateTimeImmutable $now,
    ): void {
        try {
            $this->app->make(TicketLog::class)->record($reference, $category, $email, $name, $uID, $subject, $message, $pageUrl, $status, $delivery->ticket, $delivery->reference, $delivery->url, $error, $now);
        } catch (\Throwable $e) {
            $this->logger()->error('Support ticket could not be recorded in the ticket list.', ['reference' => $reference, 'error' => $e->getMessage()]);
        }
    }

    public function on_start()
    {
        parent::on_start();

        $this->settings = $this->app->make(SupportSettings::class);
    }

    public function on_before_render()
    {
        parent::on_before_render();

        $queue = $this->app->make(QueueService::class);
        $now = new \DateTimeImmutable();

        $this->set('token', $this->token);
        $this->set('disabledReason', $this->settings->disabledReason());
        $this->set('categories', TicketCategory::cases());
        $this->set('hoursText', t(SupportDefaults::HOURS_TEXT));
        $this->set('fieldErrors', $this->fieldErrors);
        $this->set('values', $this->values + $this->defaults());
        $this->set('fileLimit', TicketValidator::effectiveFileLimit());
        $this->set('maxFiles', TicketValidator::MAX_FILES);
        $this->set('allowedMime', TicketValidator::ALLOWED_MIME);
        $this->set('queued', $queue->listForDashboard());
        $this->set('stats', $queue->stats());
        $this->set('overdue', $queue->oldestOverdue($now));
        $this->set('scheduled', $this->app->make(RetrySchedule::class)->isScheduled());
        $this->set('isSuperUser', $this->app->make(User::class)->isSuperUser());
        $this->set('dateHelper', $this->app->make('date'));
    }

    public function view() {}

    public function submit()
    {
        if ($this->postTooLarge()) {
            $this->error->add(t('The upload was too large to receive. Attach fewer or smaller files (up to %s MB in total).', TicketValidator::MAX_TOTAL_BYTES / 1048576));

            return;
        }

        if (!$this->validateToken('lgt_support_submit')) {
            return;
        }

        if (!$this->settings->isEnabled()) {
            $this->error->add(t('Support requests cannot be sent from this site.'));

            return;
        }

        $this->values = $this->request->request->all();
        $user = $this->app->make(User::class);
        $uID = (int) $user->getUserID();
        $now = new \DateTimeImmutable();

        $limiter = $this->app->make(UserRateLimiter::class);
        if (!$limiter->isAllowed($uID, $now)) {
            $this->error->add(t('You have sent several requests in the last hour. Please wait a while, or contact us directly. %s', $this->fallbackText()));

            return;
        }

        $files = $this->request->files->get('attachments') ?? [];
        $result = $this->app->make(TicketValidator::class)->validate($this->values, is_array($files) ? $files : [$files]);
        if (!$result->isValid()) {
            $this->fieldErrors = $result->errors;
            $this->error->add(t('Please correct the highlighted fields.'));

            return;
        }

        $limiter->hit($uID, $now);
        $submission = $result->submission;
        $builder = $this->app->make(TicketPayloadBuilder::class);
        $reference = LocalReference::generate($now);
        $diagnostics = $this->app->make(DiagnosticsCollector::class)->collect($submission->pageID, $submission->pageVersionID);
        $subject = $builder->subject($submission->category, $submission->summary, $this->settings->subjectPrefix());
        $message = $builder->message($submission, $reference, $now, $diagnostics->toText(), $diagnostics->recentErrors);

        try {
            $payload = $builder->payload($submission->email, $submission->name, $subject, $message, $submission->pageUrl, $submission->attachments);
        } catch (\Throwable $e) {
            $this->logger()->error('Support ticket attachments could not be read.', ['error' => $e->getMessage()]);
            $this->error->add(t('Your attachments could not be read. Please try again.'));

            return;
        }

        $delivery = $this->app->make(TicketSender::class)->send($payload, TicketSender::INTERACTIVE_TIMEOUT);
        $log = fn(string $status, ?string $error = null) => $this->logTicket($reference, $submission->category->value, $submission->email, $submission->name, $uID ?: null, $subject, $message, $submission->pageUrl, $status, $delivery, $error, $now);

        if ($delivery->isDelivered()) {
            $log(TicketLog::STATUS_DELIVERED);
            $this->flash('success', $this->deliveredText($delivery, $submission->email));
            $this->redirect('/dashboard/support');
        }

        if (!$delivery->shouldRetry()) {
            // A 400 lists what the helpdesk rejected; queueing would only fail again.
            $detail = $delivery->statusCode === 400 && $delivery->error ? ' ' . t('The helpdesk said: %s', $delivery->error) : '';
            $this->error->add(t('We couldn\'t submit your request.') . $detail . ' ' . $this->fallbackText());
            $log(TicketLog::STATUS_FAILED, $delivery->error);

            return;
        }

        try {
            $this->app->make(QueueService::class)->enqueue(
                $reference,
                $submission->email,
                $submission->name,
                $subject,
                $message,
                $submission->pageUrl,
                $uID ?: null,
                $submission->attachments,
                $delivery,
                $now,
            );
        } catch (\Throwable $e) {
            $this->logger()->error('Support ticket could not be queued.', ['reference' => $reference, 'error' => $e->getMessage()]);
            $this->error->add(t('We couldn\'t submit your request.') . ' ' . $this->fallbackText());
            $log(TicketLog::STATUS_FAILED, t('The ticket could not be queued for retry.'));

            return;
        }

        $log(TicketLog::STATUS_QUEUED, $delivery->error);
        $this->flash('success', t('Our helpdesk couldn\'t be reached just now. Your request has been saved (reference %s) and will be sent automatically.', $reference));
        $this->redirect('/dashboard/support');
    }

    public function discard($id = null)
    {
        if (!$this->validateToken('lgt_support_discard')) {
            return;
        }

        if ($this->app->make(QueueService::class)->discard((string) $id)) {
            $this->flash('success', t('The failed request was discarded.'));
        } else {
            $this->flash('message', t('Only failed requests can be discarded.'));
        }

        $this->redirect('/dashboard/support');
    }
}
