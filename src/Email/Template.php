<?php

namespace Yakamara\YForm\Email;

use Redaxo\Core\Content\Article;
use Redaxo\Core\Core;
use Redaxo\Core\Database\Exception\SqlException;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Filesystem\File;
use Redaxo\Core\Mailer\Mailer;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class Template
{
    /**
     * @throws SqlException
     * @return false|mixed
     */
    public static function getTemplate(string $name)
    {
        $template = Sql::factory();
        $tpls = $template->getArray('select * from ' . Core::getTablePrefix() . 'yform_email_template where name=:name', [':name' => $name]);
        if (1 == count($tpls)) {
            return $tpls[0];
        }
        return false;
    }

    /**
     * @throws SqlException
     * @return false|mixed
     */
    public static function getTemplateById(int $template_id)
    {
        $template = Sql::factory();
        $tpls = $template->getArray('select * from ' . Core::getTablePrefix() . 'yform_email_template where id=:template_id', [':template_id' => $template_id]);
        if (1 == count($tpls)) {
            return $tpls[0];
        }
        return false;
    }

    public static function replaceVars(array $template, $er = [])
    {
        $r = Extension::dispatch(new ExtensionPoint(
            'YFORM_EMAIL_BEFORE_REPLACEVARS',
            [
                'template' => $template,
                'search_replace' => $er,
                'status' => false,
            ],
        ));

        $template = $r['template'];
        $er = $r['search_replace'];
        $status = $r['status'];

        if ($status) {
            return true;
        }

        $er['REX_SERVER'] = Core::getServer();
        $er['REX_ERROR_EMAIL'] = Core::getErrorEmail();
        $er['REX_SERVERNAME'] = Core::getServerName();
        $er['REX_NOTFOUND_ARTICLE_ID'] = Article::getNotfoundArticleId();
        $er['REX_ARTICLE_ID'] = Article::getCurrentId();

        $template['mail_from'] = TemplateRenderer::render((string) ($template['mail_from'] ?? ''), $er);
        $template['mail_from_name'] = TemplateRenderer::render((string) ($template['mail_from_name'] ?? ''), $er);
        $template['mail_reply_to'] = TemplateRenderer::render((string) ($template['mail_reply_to'] ?? ''), $er);
        $template['mail_reply_to_name'] = TemplateRenderer::render((string) ($template['mail_reply_to_name'] ?? ''), $er);
        $template['subject'] = TemplateRenderer::render((string) ($template['subject'] ?? ''), $er);
        $template['body'] = TemplateRenderer::render((string) ($template['body'] ?? ''), $er);
        $template['body_html'] = TemplateRenderer::render((string) ($template['body_html'] ?? ''), $er);

        $template['mail_from'] = self::makeSingleLine($template['mail_from']);
        $template['mail_from_name'] = self::makeSingleLine($template['mail_from_name']);
        $template['mail_reply_to'] = self::makeSingleLine($template['mail_reply_to']);
        $template['mail_reply_to_name'] = self::makeSingleLine($template['mail_reply_to_name']);
        $template['subject'] = self::makeSingleLine($template['subject']);

        return $template;
    }

    public static function makeSingleLine(string $str): string
    {
        $str = str_replace("\n", '', $str);
        $str = str_replace("\r", '', $str);
        return $str;
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @return bool
     */
    public static function sendMail(array $template, string $template_name = '')
    {
        $r = Extension::dispatch(new ExtensionPoint(
            'YFORM_EMAIL_BEFORE_SEND',
            [
                'template' => $template,
                'template_name' => $template_name,
                'status' => false,
            ],
        ));

        $template = $r['template'];
        $template_name = $r['template_name'];
        $status = $r['status'];

        if ($status) {
            return true;
        }

        $mail = new Mailer();
        $mail->AddAddress($template['mail_to'], $template['mail_to_name']);
        $mail->SetFrom($template['mail_from'], $template['mail_from_name']);

        if ('' != $template['mail_reply_to']) {
            $mail->AddReplyTo($template['mail_reply_to'], $template['mail_reply_to_name']);
        }

        $mail->Subject = $template['subject'];
        $mail->Body = $template['body'];
        if ('' != $template['body_html']) {
            $mail->MsgHTML($template['body_html']);
            if ('' != $template['body']) {
                $mail->AltBody = $template['body'];
            }
        } else {
            $mail->Body = $template['body'];
        }

        if (is_array($template['attachments'])) {
            foreach ($template['attachments'] as $f) {
                $mail->AddAttachment($f['path'], $f['name']);
            }
        }

        Extension::dispatch(new ExtensionPoint('YFORM_EMAIL_SEND', $mail, $template));

        if ($mail->Send()) {
            $template['email_subject'] = $template['subject'];
            Extension::dispatch(new ExtensionPoint('YFORM_EMAIL_SENT', $template_name, $template, true)); // read only
            return true;
        }
        $template['email_subject'] = $template['subject'];
        Extension::dispatch(new ExtensionPoint('YFORM_EMAIL_SENT_FAILED', $template_name, $template, true)); // read only
        return false;
    }
}
