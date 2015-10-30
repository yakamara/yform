<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class rex_yform_action_abstract extends rex_yform_base_abstract
{
    var $action = array();

    protected function getElementMappingOffset()
    {
        return 1;
    }

}
