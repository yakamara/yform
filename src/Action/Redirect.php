<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\Attribute\AsAction;
use Redaxo\Core\Filesystem\Url;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsAction('redirect')]
class Redirect extends AbstractAction
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

        $u = (string) $this->getElement(2);
        $u1 = (string) (int) $u;

        if ($u === $u1) {
            // id -> intern article
            //
            // REDAXO 5's rex_getUrl($id, $clang, $params, $separator) took a string clang and a
            // separator; Url::article() is article(?int $id, ?int $clang, array $params). Passing
            // the old argument list threw a TypeError on $clang for *every* internal redirect.
            $url = Url::article((int) $u);
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
