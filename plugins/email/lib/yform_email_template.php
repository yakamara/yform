<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_email_template
{
    static function getTemplate($name)
    {

        $gt = rex_sql::factory();
        $gt->setQuery('select * from ' . rex::getTablePrefix() . 'yform_email_template where name=:name',[':name' => $name]);
        if ($gt->getRows() == 1) {
            $b = $gt->getArray();
            return current($b);
        }
        return false;
    }

    static function replaceVars($template, $er = array())
    {

        $r = rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_BEFORE_REPLACEVARS',
            [
                'template' => $template,
                'search_replace' => $er,
                'status' => false
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

        $template['mail_from'] = rex_var::parse($template['mail_from'],'','yform_email_template', $er);
        $template['mail_from_name'] = rex_var::parse($template['mail_from_name'],'','yform_email_template', $er);

        $template['subject'] = rex_var::parse($template['subject'],'','yform_email_template', $er);
        $template['body'] = rex_var::parse($template['body'],'','yform_email_template', $er);
        $template['body_html'] = rex_var::parse($template['body_html'],'','yform_email_template', $er);

        // rex_vars bug: sonst wird der Zeilenumbruch entfernt - wenn DATA_VAR am Ende einer Zeile
        if (rex_string::versionCompare(rex::getVersion(), '5.0.1', '<')) {
            $template['body'] = str_replace("?>\r", "?>\r\n\r", $template['body']);
            $template['body'] = str_replace("?>\n", "?>\n\r\n", $template['body']);
            $template['body_html'] = str_replace("?>\r", "?>\r\n\r", $template['body_html']);
            $template['body_html'] = str_replace("?>\n", "?>\n\r\n", $template['body_html']);
        }

        $template['mail_from'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/mail_from', $template['mail_from']));
        $template['mail_from_name'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/mail_from_name', $template['mail_from_name']));
        $template['subject'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/subject', $template['subject']));
        $template['body'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/body', $template['body']));
        $template['body_html'] = rex_file::getOutput(rex_stream::factory('yform/email/template/'.$template['name'].'/body_html', $template['body_html']));

        $template['mail_from'] = self::makeSingleLine($template['mail_from']);
        $template['subject'] = self::makeSingleLine($template['subject']);

        return $template;

    }

    static function makeSingleLine($str)
    {
        $str = str_replace("\n", '', $str);
        $str = str_replace("\r", '', $str);
        return $str;
    }

    static function sendMail($template, $template_name = '')
    {

        $r = rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_BEFORE_SEND',
            [
                'template' => $template,
                'template_name' => $template_name,
                'status' => false
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
        $mail->Subject = $template['subject'];
        $mail->Body = $template['body'];
        if ($template['body_html'] != '') {
            $mail->AltBody = $template['body'];
            $mail->MsgHTML($template['body_html']);
        } else {
            $mail->Body = strip_tags($template['body']);
        }

        if (is_array($template['attachments'])) {
            foreach ($template['attachments'] as $f) {
                $mail->AddAttachment($f['path'], $f['name']);
            }
        }

        if ($mail->Send()) {
            $template['email_subject'] = $template['subject'];
            rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_SENT', $template_name, $template, true)); // read only
            return true;

        } else {
            $template['email_subject'] = $template['subject'];
            rex_extension::registerPoint(new rex_extension_point('YFORM_EMAIL_SENT_FAILED', $template_name, $template, true)); // read only
            return false;

        }

    }

}
