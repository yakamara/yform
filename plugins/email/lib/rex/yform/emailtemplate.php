<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_emailtemplate
{
    static function getTemplate($name)
    {
        global $REX;
        $gt = rex_sql::factory();
        $gt->setQuery('select * from ' . rex::getTablePrefix() . 'yform_email_template where name="' . mysql_real_escape_string($name) . '"');
        if ($gt->getRows() == 1) {
            $b = $gt->getArray();
            return current($b);
        }
        return false;
    }

    static function replaceVars($template, $er = array())
    {
        global $REX;

        $r = rex_register_extension_point('YFORM_EMAIL_BEFORE_REPLACEVARS', array(
            'template' => $template,
            'search_replace' => $er,
            'status' => false
        )
        );

        $template = $r['template'];
        $er = $r['search_replace'];
        $status = $r['status'];

        if ($status) {
            return true;
        }

        $er['REX_SERVER'] = $REX['SERVER'];
        $er['REX_ERROR_EMAIL'] = $REX['ERROR_EMAIL'];
        $er['REX_SERVERNAME'] = $REX['SERVERNAME'];
        $er['REX_NOTFOUND_ARTICLE_ID'] = $REX['NOTFOUND_ARTICLE_ID'];
        $er['REX_ARTICLE_ID'] = $REX['ARTICLE_ID'];
        foreach ($er as $search => $replace) {
            foreach ($template as $k => $v) {
                $template[$k] = str_replace('###' . $search . '###', $replace, $template[$k]);
                $template[$k] = str_replace('***' . $search . '***', urlencode($replace), $template[$k]);
                $template[$k] = str_replace('+++' . $search . '+++', self::makeSingleLine($replace), $template[$k]);
                
                $template[$k] = preg_replace_callback(
                  '@redaxo://(\d+)(?:-(\d+))?/?@i',
                  create_function(
                    '$matches',
                    'return rex_getUrl($matches[1], isset($matches[2]) ? $matches[2] : ' . $REX['CUR_CLANG'] . ');'
                  ),
                  $template[$k]
                );
            }
        }
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

        $r = rex_register_extension_point('yform_EMAIL_BEFORE_SEND', array(
            'template' => $template,
            'template_name' => $template_name,
            'status' => false
        )
        );

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
            rex_register_extension_point('yform_EMAIL_SENT', $template_name, $template, true); // read only
            return true;

        } else {
            $template['email_subject'] = $template['subject'];
            rex_register_extension_point('yform_EMAIL_SENT_FAILED', $template_name, $template, true); // read only
            return false;

        }

    }

}
