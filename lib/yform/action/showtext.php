<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_showtext extends rex_yform_action_abstract
{

    function executeAction()
    {
        $text = $this->getElement(2);

        if ($text == '') {
            $text = $this->params['answertext'];
        }

        $text = rex_i18n::translate($text, null);

        if ($this->getElement(5) == '0') {
            $text = nl2br(htmlspecialchars($text));
        }

        if ($this->getElement(5) == '2') {
            $text = htmlspecialchars_decode($text);
            $text = str_replace('<br />', '', $text);
            $text = str_replace('&#039;', '\'', $text);
            $text = rex_textile::parse($text);
        }

        $text = $this->getElement(3) . $text . $this->getElement(4);

        foreach ($this->params['value_pool']['email'] as $search => $replace) {
            $text = str_replace('###' . $search . '###', $replace, $text);
        }

        $this->params['output'] .= $text;
    }

    function getDescription()
    {
        return 'action|showtext|Antworttext|&lt;p&gt;|&lt;/p&gt;|0/1/2 (plaintext/html/textile)';
    }

}
