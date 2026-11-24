<?php

namespace LgtToolkit\Providers\LgtMail;

use Core;
use Throwable;
use LgtToolkit\Mail\SendEmailRequest;
use Concrete\Core\Error\ErrorList\ErrorList;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Concrete\Core\File\Service\File as FileService;

/**
 * Renders and sends package email templates.
 */
class LgtMailService
{
    protected ErrorList $error;

    /** @var list<string> */
    protected array $templatePaths = [];

    /**
     * Lists the template directories to search, most specific first.
     * The package or application folder wins; the toolkit's own mail/ folder is the fallback.
     *
     * @param SendEmailRequest $request The email request being processed.
     *
     * @return list<string> The template directories.
     */
    protected function getTemplatePaths(SendEmailRequest $request): array
    {
        return [
            $request->pkg ? $request->pkg->getPackagePath() . '/mail' : DIR_APPLICATION . '/mail',
            dirname(__DIR__, 3) . '/mail',
        ];
    }

    /**
     * Reads an email template from disk.
     *
     * @param FileService $fh           The file helper service.
     * @param string      $templateName The template file name.
     *
     * @return string The template contents.
     */
    protected function readTemplate(FileService $fh, string $templateName): string
    {
        foreach ($this->templatePaths as $path) {
            $file = sprintf('%s/%s.php', $path, $templateName);
            if (is_file($file) && ($template = $fh->getContents($file)) !== false) {
                return $template;
            }
        }

        throw new \RuntimeException(t('Template "%s" not found', $templateName));
    }

    /**
     * Replaces loop placeholders in the email content.
     *
     * @param FileService      $fh           The file helper service.
     * @param SendEmailRequest $request      The email request payload.
     * @param string           $emailContent The current email content.
     *
     * @return string The processed email content.
     */
    protected function applyTemplateLoop(FileService $fh, SendEmailRequest $request, string $emailContent): string
    {
        if (!$request->template_loop) {
            return $emailContent;
        }

        $loopReplacements = $request->args['replace']['loop'] ?? [];
        if (!is_array($loopReplacements) || $loopReplacements === []) {
            return $emailContent;
        }

        $loopTemplate = $this->readTemplate($fh, $request->template_loop);
        $loopReplace = '';

        foreach ($loopReplacements as $looping) {
            if (!is_array($looping)) {
                continue;
            }

            $tempLoop = $loopTemplate;

            foreach ($looping as $handle => $value) {
                $tempLoop = str_replace('{{' . $handle . '}}', (string) $value, $tempLoop);
            }

            $loopReplace .= $tempLoop;
        }

        return str_replace('{{loop_replace}}', $loopReplace, $emailContent);
    }

    /**
     * Builds the final email body using the configured templates.
     *
     * @param FileService      $fh      The file helper service.
     * @param SendEmailRequest $request The current email request.
     *
     * @return string The rendered email body.
     */
    protected function renderBody(FileService $fh, SendEmailRequest $request): string
    {
        $this->templatePaths = $this->getTemplatePaths($request);

        $emailTemplate = $this->readTemplate($fh, $request->body_template ?? 'email_template');
        $emailContent = $this->readTemplate($fh, $request->template ?? 'default');
        $emailContent = $this->applyTemplateLoop($fh, $request, $emailContent);

        $replacements = $request->args['replace'] ?? [];
        if (isset($replacements['loop'])) {
            unset($replacements['loop']);
        }

        $body = str_replace('{{email_content}}', $emailContent, $emailTemplate);
        $body = $this->applyReplacements($body, $replacements);
        $body = str_replace('{{base_url}}', $this->getBaseUrl(), $body);

        if (!array_key_exists('footer', $replacements)) {
            $body = str_replace('{{footer}}', '', $body);
        }

        return $body;
    }

    /**
     * Replaces template variables with the supplied replacements.
     *
     * @param string $body         The body text to transform.
     * @param array  $replacements The replacement values keyed by token name.
     *
     * @return string The processed body text.
     */
    protected function applyReplacements(string $body, array $replacements): string
    {
        foreach ($replacements as $handle => $value) {
            $body = str_replace('{{' . $handle . '}}', (string) $value, $body);
        }

        return $body;
    }

    /**
     * Returns the base URL used for email template links.
     *
     * @return string The base URL for the current installation.
     */
    protected function getBaseUrl(): string
    {
        return rtrim((string) Core::make('url/canonical'), '/');
    }

    /**
     * Validates the email request arguments before sending.
     *
     * @param SendEmailRequest $request The email request to validate.
     */
    protected function checkRequestForErrors(SendEmailRequest $request)
    {
        if (!array_key_exists('to', $request->args)) {
            $this->error->add(t('The "To" field is required'));
        }

        if (!array_key_exists('subject', $request->args)) {
            $this->error->add(t('The "Subject" field is required'));
        }
    }

    /**
     * Sends the processed email through the application mailer.
     *
     * @param SendEmailRequest $request The email request to send.
     *
     * @return Response The JSON response returned by the mailer.
     */
    public function sendEmail(SendEmailRequest $request): Response
    {
        // Fresh per call: the service is a container singleton.
        $this->error = new ErrorList();
        $this->checkRequestForErrors($request);

        if ($this->error->has()) {
            return new JsonResponse([
                'success' => false,
                'errors' => $this->error->getList(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $mh = Core::make('mail');
        $fh = Core::make('helper/file');

        try {
            $body = $this->renderBody($fh, $request);

            // Set TO
            if (is_array($request->args['to'])) {
                foreach ($request->args['to'] as $recipient) {
                    $mh->to($recipient);
                }
            } else {
                $mh->to($request->args['to']);
            }

            // Set BCC
            if (array_key_exists('bcc', $request->args)) {
                if (is_array($request->args['bcc'])) {
                    foreach ($request->args['bcc'] as $recipient) {
                        $mh->bcc($recipient);
                    }
                } else {
                    $mh->bcc($request->args['bcc']);
                }
            }

            // Set ReplyTo
            if (array_key_exists('reply_to', $request->args)) {
                $mh->replyto($request->args['reply_to']);
            }

            // Set From (sendMail() falls back to concrete.email.default address + name)
            if (array_key_exists('from', $request->args)) {
                $mh->from($request->args['from']);
            }

            $mh->setSubject($request->args['subject']);
            $mh->setBodyHTML($body);
            $mh->setBody($body);

            $mh->setTesting($request->testing ?? false);

            $sent = $mh->sendMail();

            return new JsonResponse([
                'success' => $sent,
            ], $sent ? Response::HTTP_ACCEPTED : Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            // Mailer exception codes can be SMTP codes, so never use them as the HTTP status.
            return new JsonResponse([
                'success' => false,
                'errors' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
