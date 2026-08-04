<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\Attribute\AsAction;
use Redaxo\Core\Translation\I18n;

use function Redaxo\Core\View\escape;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsAction('showtext')]
class ShowText extends AbstractAction
{
    public function executeAction(): void
    {
        $text = $this->getElement(2);

        $text = I18n::translate($text, false);

        if ('0' == $this->getElement(5)) {
            $text = nl2br(escape($text));
        }

        $text = $this->getElement(3) . $text . $this->getElement(4);

        foreach ($this->params['value_pool']['email'] as $search => $replace) {
            if (is_scalar($search) && is_scalar($replace)) {
                $text = str_replace('###' . $search . '###', $replace, $text);
            }
        }

        $this->params['output'] .= $text;
    }

    public function getDescription(): string
    {
        return 'action|showtext|Antworttext|&lt;p&gt;|&lt;/p&gt;|[0 for specialchars + nl2br]';
    }
}
