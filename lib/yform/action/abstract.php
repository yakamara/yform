<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class rex_yform_action_abstract extends rex_yform_base_abstract
{
    public $action = [];

    protected function getElementMappingOffset()
    {
        return 1;
    }
    
    public function parse($template, $params = [])
    {
        extract($params);
        ob_start();
        include $this->params['this']->getTemplatePath($template);
        return ob_get_clean();
    }
}
