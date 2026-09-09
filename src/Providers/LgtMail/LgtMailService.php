<?php

namespace LgtToolkit\Providers\LgtMail;

use Core;
use Exception;
use LgtToolkit\Mail\SendEmailRequest;
use Concrete\Core\Error\ErrorList\ErrorList;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Concrete\Core\File\Service\File as FileService;

class LgtMailService
{
    protected ErrorList $error;
    protected string $templateBasePath;

    protected function getTemplateBasePath(SendEmailRequest $request): string
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

        if ($request->pkg) {
            return rtrim($documentRoot, DIRECTORY_SEPARATOR) . '/' . ltrim($request->pkg->getRelativePath(), '/') . '/mail';
        }

        return rtrim($documentRoot, DIRECTORY_SEPARATOR) . '/application/mail';
    }

    protected function readTemplate(FileService $fh, string $templateName): string
    {
        $template = $fh->getContents(sprintf('%s/%s.php', $this->templateBasePath, $templateName));

        if (!$template) {
            throw new Exception(t('Template "%s" not found', $templateName));
        }

        return $template;
    }

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

        unset($request->args['replace']['loop']);

        return str_replace('{{loop_replace}}', $loopReplace, $emailContent);
    }

    protected function applyReplacements(string $body, array $replacements): string
    {
        foreach ($replacements as $handle => $value) {
            $body = str_replace('{{' . $handle . '}}', (string) $value, $body);
        }

        return $body;
    }

    protected function getBaseUrl(): string
    {
        return 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    protected function checkRequestForErrors(SendEmailRequest $request)
    {
        if (!array_key_exists('to', $request->args)) {
            $this->error->add(t('The "To" field is required'));
        }

        if (!array_key_exists('subject', $request->args)) {
            $this->error->add(t('The "Subject" field is required'));
        }
    }

    public function sendEmail(SendEmailRequest $request): Response
    {
        $this->checkRequestForErrors($request);

        if ($this->error->has()) {
            return new JsonResponse([
                'success' => false,
                'errors' => $this->error->getList(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $mh = Core::make('mail');
        $fh = Core::make('helper/file');
        $config = Core::make('config');
        $this->templateBasePath = $this->getTemplateBasePath($request);

        try {
            $emailTemplate = $this->readTemplate($fh, $request->body_template ?? 'email_template');
            $emailContent = $this->readTemplate($fh, $request->template);
            $emailContent = $this->applyTemplateLoop($fh, $request, $emailContent);

            $body = str_replace('{{email_content}}', $emailContent, $emailTemplate);
            $body = $this->applyReplacements($body, $request->args['replace'] ?? []);
            $body = str_replace('{{base_url}}', $this->getBaseUrl(), $body);

            if (!array_key_exists('footer', $request->args['replace'] ?? [])) {
                $body = str_replace('{{footer}}', '', $body);
            }

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

            // Set From
            if (array_key_exists('from', $request->args)) {
                $mh->from($request->args['from']);
            } else {
                $mh->from($config->get('concrete.email.default.address'));
            }

            $mh->setSubject($request->args['subject']);
            $mh->setBodyHTML($body);
            $mh->setBody($body);

            $mh->setTesting($request->testing ?? false);

            return new JsonResponse([
                'success' => $mh->sendMail(),
            ], Response::HTTP_ACCEPTED);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'errors' => $e->getMessage(),
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
