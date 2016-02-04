<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_tpl2email extends rex_yform_action_abstract
{

    function executeAction()
    {

        $template_name = $this->getElement(2);
        if ($etpl = rex_yform_email_template::getTemplate($template_name)) {

            $mail_to = rex::getErrorEmail();

            if ($this->getElement(3) != false && $this->getElement(3) != '') {
                foreach ($this->params['value_pool']['email'] as $key => $value) {
                    if ($this->getElement(3) == $key) {
                        $mail_to = $value;
                        break;
                    }
                }
            }

            // ---- fix mailto from definition
            if ($this->getElement(4) != false && $this->getElement(4) != '') {
                $mail_to = $this->getElement(4);
            }

            if ($this->params['debug']) {
                echo '<hr /><pre>'; var_dump($etpl); echo '</pre><hr />';
            }

            $etpl = rex_yform_email_template::replaceVars($etpl, $this->params['value_pool']['email']);

            if ($this->params['debug']) {
                echo '<hr /><pre>'; var_dump($etpl); echo '</pre><hr />';
            }

            $etpl['mail_to'] = $mail_to;
            $etpl['mail_to_name'] = $mail_to;

            if ($etpl['attachments'] != '') {
                $f = explode(',', $etpl['attachments']);
                $etpl['attachments'] = array();
                foreach ($f as $v) {
                    $etpl['attachments'][] = array('name' => $v, 'path' => rex_path::media($v));
                }

            } else {
                $etpl['attachments'] = array();
            }

            if (!rex_yform_email_template::sendMail($etpl, $template_name)) {
                if ($this->params['debug']) {
                    echo 'email could be sent';
                }
                return false;

            } else {
                if ($this->params['debug']) {
                    echo 'email sent';                }
                return true;

            }

        } else {
            if ($this->params['debug']) {
                echo '<p>Template: "'.htmlspecialchars($template_name).'" not found';
            }

        }
        return false;

    }

    function getDescription()
    {
        return 'action|tpl2email|emailtemplate|emaillabel|[email@domain.de]';

    }

}
