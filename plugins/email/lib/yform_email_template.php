<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_email_template
{
    /**
     * @throws rex_sql_exception
     * @return false|mixed
     */
    public static function getTemplate(string $name)
    {
        $template = rex_sql::factory();
        $tpls = $template->getArray('select * from ' . rex::getTablePrefix() . 'yform_email_template where name=:name', [':name' => $name]);
        if (1 == count($tpls)) {
            return $tpls[0];
        }
        return false;
    }

    public static function replaceVars(array $template, $er = [])
    {
        $r = rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_BEFORE_REPLACEVARS',
            [
                'template' => $template,
                'search_replace' => $er,
                'status' => false,
            ]
        ));

        $template = $r['template'];
        $er = $r['search_replace'];
        $status = $r['status'];

        if ($status) {
            return true;
        }

        $er['REX_SERVER'] = rex::getServer();
        $er['REX_ERROR_EMAIL'] = rex::getErrorEmail();
        $er['REX_SERVERNAME'] = rex::getServerName();
        $er['REX_NOTFOUND_ARTICLE_ID'] = rex_article::getNotfoundArticleId();
        $er['REX_ARTICLE_ID'] = rex_article::getCurrentId();

        foreach ($template as $k => $v) {
            foreach ($er as $er_key => $er_value) {
                /** @deprecated will be removed. use REX_YFORM_DATA[field="name"] */
                $template[$k] = str_replace('###' . $er_key . '###', $er_value, $template[$k]);
                /** @deprecated will be removed. use REX_YFORM_DATA[field="name"] */
                $template[$k] = str_replace('***' . $er_key . '***', urlencode($er_value), $template[$k]);
                /** @deprecated will be removed. use REX_YFORM_DATA[field="name"] */
                $template[$k] = str_replace('+++' . $er_key . '+++', self::makeSingleLine($er_value), $template[$k]);
            }
            $template[$k] = rex_var::parse($template[$k], '', 'yform_email_template', $er);
        }

        $template['mail_from'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/mail_from', $template['mail_from']));
        $template['mail_from_name'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/mail_from_name', $template['mail_from_name']));
        $template['mail_reply_to'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/mail_reply_to', $template['mail_reply_to']));
        $template['mail_reply_to_name'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/mail_reply_to_name', $template['mail_reply_to_name']));
        $template['subject'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/subject', $template['subject']));
        $template['body'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/body', $template['body']));
        $template['body_html'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/body_html', $template['body_html']));

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
        $r = rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_BEFORE_SEND',
            [
                'template' => $template,
                'template_name' => $template_name,
                'status' => false,
            ]
        ));

        $template = $r['template'];
        $template_name = $r['template_name'];
        $status = $r['status'];

        if ($status) {
            return true;
        }

        $mail = new rex_mailer();
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
            $mail->Body = strip_tags($template['body']);
        }

        if (is_array($template['attachments'])) {
            foreach ($template['attachments'] as $f) {
                $mail->AddAttachment($f['path'], $f['name']);
            }
        }

        rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_SEND', $mail, $template));

        if ($mail->Send()) {
            $template['email_subject'] = $template['subject'];
            rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_SENT', $template_name, $template, true)); // read only
            return true;
        }
        $template['email_subject'] = $template['subject'];
        rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_SENT_FAILED', $template_name, $template, true)); // read only
        return false;
    }
}
