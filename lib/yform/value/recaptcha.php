<?php

/**
 * YForm.
 *
 * @author p.schulze@bitshifters.de, Jan Kristinus
 * @author <a href="http://www.bitshifters.de">www.bitshifters.de</a>
 */

class rex_yform_value_recaptcha extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $publicKey = $this->getElement(2);
        $privateKey = $this->getElement(3);
        $errorMessage = $this->getElement(4);
        $loadScript = $this->getElement(5);

        if ($this->params['send'] == 1) {
            $ObjectId = $this->getId();
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, [
                    'secret' => $privateKey,
                    'response' => $_POST['g-recaptcha-response'],
                    'remoteip' => $_SERVER['REMOTE_ADDR'],
                ]);

                $res = json_decode(curl_exec($ch));
                $res = $res->success;

                if (!$res) {
                    $this->params['warning_messages'][$ObjectId] = $errorMessage;
                }
            } catch (Exception $e) {
                $this->params['warning'][$ObjectId] = $this->params['error_class'];
                $this->params['warning_messages'][$ObjectId] = $errorMessage;
            }
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.recaptcha.tpl.php', compact('publicKey', 'loadScript'));
    }

    public function getDescription()
    {
        return 'recaptcha|name|public_key|private_key|error_message|load_script[1,0]|';
    }
}
