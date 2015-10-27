<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_db2email extends rex_yform_action_abstract
{

    function executeAction()
    {

        global $REX;

        $template_name = $this->getElement(2);

        if ($etpl = rex_yform_emailtemplate::getTemplate($template_name)) {

            // ----- find mailto
            $mail_to = $REX['ERROR_EMAIL']; // default

            // finde email label in list
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

            $etpl = rex_yform_emailtemplate::replaceVars($etpl, $this->params['value_pool']['email']);

            $etpl['mail_to'] = $mail_to;
            $etpl['mail_to_name'] = $mail_to;

            if ($etpl['attachments'] != '') {
                $f = explode(',', $etpl['attachments']);
                $etpl['attachments'] = array();
                foreach ($f as $v) {
                    $etpl['attachments'][] = array('name' => $v, 'path' => $REX['INCLUDE_PATH'] . '/../../files/' . $v);
                }

            } else {
                $etpl['attachments'] = array();
            }

            if ($this->params['debug']) {
                echo '<hr /><pre>'; var_dump($etpl); echo '</pre><hr />';
            }

            if (!rex_yform_emailtemplate::sendMail($etpl, $template_name)) {
                echo 'error - email sent';
                return false;

            } else {
                return true;

            }

        }
        return false;

    }

    function getDescription()
    {
        return 'action|db2email|emailtemplate|emaillabel|[email@domain.de]';

    }

}
