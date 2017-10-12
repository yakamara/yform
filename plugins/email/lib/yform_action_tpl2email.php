<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_tpl2email extends rex_yform_action_abstract
{
    public function executeAction()
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

            if ($this->getElement(5) != false && $this->getElement(5) != '') {
                $mail_to_name = $this->getElement(5);
            } else {
                $mail_to_name = $mail_to;
            }

            if ($this->params['debug']) {
                dump($etpl);
            }

            $etpl = rex_yform_email_template::replaceVars($etpl, $this->params['value_pool']['email']);

            $etpl['mail_to'] = $mail_to;
            $etpl['mail_to_name'] = $mail_to_name;

            if ($etpl['attachments'] != '') {
                $f = explode(',', $etpl['attachments']);
                $etpl['attachments'] = [];
                foreach ($f as $v) {
                    $etpl['attachments'][] = ['name' => $v, 'path' => rex_path::media($v)];
                }
            } else {
                $etpl['attachments'] = [];
            }

            if (isset($this->params['value_pool']['email_attachments']) && is_array($this->params['value_pool']['email_attachments'])) {
                foreach ($this->params['value_pool']['email_attachments'] as $v) {
                    $etpl['attachments'][] = ['name' => $v[0], 'path' => $v[1]];
                }
            }

            if ($this->params['debug']) {
                dump($etpl);
            }

            if (!rex_yform_email_template::sendMail($etpl, $template_name)) {
                if ($this->params['debug']) {
                    dump( 'email could not be sent');
                }
                if ($this->getElement(6) != false && $this->getElement(6) != '') {
                    $this->params['output'] .= $this->getElement(6);
                }
                return false;
            }
            if ($this->params['debug']) {
                dump( 'email sent');
            }
            return true;
        }
        if ($this->params['debug']) {
            dump( 'Template: "' . htmlspecialchars($template_name) . '" not found');
        }

        return false;
    }

    public function getDescription()
    {
        return 'action|tpl2email|emailtemplate|emaillabel|[email@domain.de]|[email_name]|[Fehlermeldung wenn Versand fehgeschlagen ist/html]';
    }
}
