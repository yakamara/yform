<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_html extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        $html = $this->getElement(2);
        echo $html;
    }

    public function getDescription(): string
    {
        return 'action|html|&lt;b&gt;fett&lt;/b&gt;';
    }
}
