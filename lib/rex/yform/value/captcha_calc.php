<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_captcha_calc extends rex_yform_value_abstract
{

    function enterObject()
    {


        require_once realpath(dirname(__FILE__) . '/../../ext/captcha_calc/class.captcha_calc_x.php');

        $captcha = new captcha_calc_x();
        $captchaRequest = rex_request('captcha_calc', 'string');

        if ($captchaRequest == 'show') {
            while (@ob_end_clean());
            $captcha->handle_request();
            exit;
        }

        if ( $this->params['send'] == 1 && $captcha->validate($this->value)) {

        } elseif ($this->params['send'] == 1) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = $this->getElement(2);
        }

        if ($this->getElement(3) != '') {
            $link = $this->getELement(3) . '?captcha_calc=show&' . time();
        } else {
            $link = rex_getUrl($this->params['article_id'], $this->params['clang'], array('captcha_calc' => 'show'), '&');
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.captcha_calc.tpl.php', array('link' => $link));
    }

    function getDescription()
    {
        return 'captcha_calc -> Beispiel: captcha|Beschreibungstext|Fehlertext';
    }
}
