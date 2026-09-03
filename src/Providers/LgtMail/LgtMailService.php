<?php

namespace LgtTookit\Providers\LgtMail;

use Core;
use Package;
use Concrete\Core\Http\Response;
use Concrete\Core\Error\UserMessageException;

//TODO: Maybe make LgtMailService a little better
class LgtMailService
{
    /**
    * Send an email from a template
    *
     * @var string      $template       Name of the email template that we need to send
     * @var array       $args           Array with a list of elements, recipients, subject, variables to replace
     * @var bool|string $template_loop  Name of the template loop if it exists
     * @var string      $body_template  If we want to change the default email body template
     * @var string|bool $pkgHandle      Package handle to get templates from a particular package
    *
     * @return bool true / false
    */
    public function sendEmail(
        string $template,
        array $args,
        $template_loop = false,
        $body_template = 'email_template',
        $pkgHandle = false,
    ) {
        if (!array_key_exists('to', $args) || !array_key_exists('subject', $args)) {
            throw new UserMessageException('To send email the To field is required', Response::HTTP_BAD_REQUEST);
        }

        if (!array_key_exists('subject', $args)) {
            throw new UserMessageException('To send email a Subject is required', Response::HTTP_BAD_REQUEST);
        }

        // Load Services
        $mh = Core::make('mail');
        $fh = Core::make('helper/file');
        $config = Core::make('config');
        $pkg = ($pkgHandle) ? Package::getByHandle($pkgHandle) : false;

        // Set mail helper testing mode on, this will throw a debug error page
        //$mh->setTesting(true);

        if ($pkg) {
            $pkgPath = $_SERVER['DOCUMENT_ROOT'] . $pkg->getRelativePath();
            $email_template = $fh->getContents($pkgPath . '/mail/' . $body_template . '.php');
            $email_content = $fh->getContents($pkgPath . '/mail/' . $template . '.php');
        } else {
            $email_template = $fh->getContents($_SERVER['DOCUMENT_ROOT'] . '/application/mail/' . $body_template . '.php');
            $email_content = $fh->getContents($_SERVER['DOCUMENT_ROOT'] . '/application/mail/' . $template . '.php');
        }

        // Check and see if we have a template loop, get and run the loop, then unset the loop replace var in the array.
        if ($template_loop) {
            if ($pkg) {
                $pkgPath = $_SERVER['DOCUMENT_ROOT'] . $pkg->getRelativePath();
                $loop = $fh->getContents($pkgPath . '/mail/' . $template_loop . '.php');
            } else {
                $loop = $fh->getContents($_SERVER['DOCUMENT_ROOT'] . '/application/mail/' . $template_loop . '.php');
            }

            $loop_args = $args['replace']['loop'];

            $loop_replace = '';

            foreach ($loop_args as $looping) {
                $temp_loop = $loop;

                foreach ($looping as $handle => $value) {
                    $temp_loop = str_replace('{{' . $handle . '}}', $value, $temp_loop);
                }

                $loop_replace .= $temp_loop;
            }

            $email_content = str_replace('{{loop_replace}}', $loop_replace, $email_content);

            unset($args['replace']['loop']);
        }

        // Add the site URL in, so we have access to it in the templates
        $args['replace']['base_url'] = 'http://' . $_SERVER['HTTP_HOST'];

        // Merge the template with our content
        // We do this before we loop through our replace, so that we can have variables in the main template too.
        $body = str_replace('{{email_content}}', $email_content, $email_template);

        foreach ($args['replace'] as $handle => $value) {
            $body = str_replace('{{' . $handle . '}}', $value, $body);
        }

        // Remove {{footer}} if we haven't sent anything
        if (!array_key_exists('footer', $args['replace'])) {
            $body = str_replace('{{footer}}', '', $body);
        }

        // Set TO
        if (is_array($args['to'])) {
            foreach ($args['to'] as $recipient) {
                $mh->to($recipient);
            }
        } else {
            $mh->to($args['to']);
        }

        // Set BCC
        if (array_key_exists('bcc', $args)) {
            if (is_array($args['bcc'])) {
                foreach ($args['bcc'] as $recipient) {
                    $mh->bcc($recipient);
                }
            } else {
                $mh->bcc($args['bcc']);
            }
        }

        // Set ReplyTo
        if (array_key_exists('reply_to', $args)) {
            $mh->replyto($args['reply_to']);
        }

        // Set From
        if (array_key_exists('from', $args)) {
            $mh->from($args['from']);
        } else {
            $mh->from($config->get('concrete.email.default.address'));
        }

        $mh->setSubject($args['subject']);
        $mh->setBodyHTML($body);
        $mh->setBody($body);

        $sent = $mh->sendMail();

        return $sent;
    }
}
