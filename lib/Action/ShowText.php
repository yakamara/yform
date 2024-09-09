<?php

namespace Yakamara\YForm\Action;

use rex_i18n;

use function is_scalar;

class ShowText extends AbstractAction
{
    public function executeAction(): void
    {
        $text = $this->getElement(2);

        $text = rex_i18n::translate($text, false);

        if ('0' == $this->getElement(5)) {
            $text = nl2br(rex_escape($text));
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
