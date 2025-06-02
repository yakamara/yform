<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_redirect extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        // spezialfaelle - nur bei request oder label
        switch ($this->getElement(3)) {
            case 'request':
                if (!isset($_REQUEST[$this->getElement(4)])) {
                    return;
                }
                break;
            case 'label':
                if (!isset($this->params['value_pool']['sql'][$this->getElement(4)])) {
                    return;
                }
                break;
        }

        $u = $this->getElement(2);
        $u1 = (string) (int) $u;

        if ($u == $u1) {
            // id -> intern article
            $url = rex_getUrl($u, '', [], '&');
        } else {
            // extern link
            $url = $u;
        }

        foreach ($this->params['value_pool']['email'] as $search => $replace) {
            if (is_array($replace)) {
                continue;
            }
            $url = str_replace('###' . $search . '###', urlencode($replace), $url);
        }

        if ('' != $url && 0 == count($this->params['warning_messages'])) {
            header('Location: ' . $url);
            $this->params['form_exit'] = true;
        }
    }

    public function getDescription(): string
    {
        return 'action|redirect|Artikel-Id oder Externer Link|request/label|field';
    }
}
