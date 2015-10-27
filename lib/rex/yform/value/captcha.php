<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_captcha extends rex_yform_value_abstract
{

    function enterObject()
    {

        global $REX;

        require_once realpath(dirname(__FILE__) . '/../../ext/captcha/class.captcha_x.php');

        $captcha = new captcha_x();
        $captchaRequest = rex_request('captcha', 'string');

        if ($captchaRequest == 'show') {
            while (@ob_end_clean());
            $captcha->handle_request();
            exit;
        }

        if ( $this->params['send'] == 1 & $captcha->validate($this->getValue())) {
            if (isset($_SESSION['captcha'])) {
                unset($_SESSION['captcha']);
            }
        } elseif ($this->params['send'] == 1) {
            // Error. Fehlermeldung ausgeben
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = $this->getElement(2);
        }

        if ($this->getElement(3) != '') {
            $link = $this->getElement(3) . '?captcha=show&' . time();
        } else {
            $link = rex_getUrl($this->params['article_id'], $this->params['clang'], array('captcha' => 'show'), '&') . '&' . time() . microtime();
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.captcha.tpl.php', array('link' => $link));
    }

    function getDescription()
    {
        return 'captcha -> Beispiel: captcha|Beschreibungstext|Fehlertext|[link]';
    }

}
