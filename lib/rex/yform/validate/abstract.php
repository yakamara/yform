<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class rex_yform_validate_abstract extends rex_yform_base_abstract
{
    var $obj_array;

    function setObjects(&$Objects)
    {
        parent::setObjects($Objects);

        $tmp_Objects = explode(',', $this->getElement(2));

        foreach ($tmp_Objects as $tmp_Object) {
            $tmp_FoundObject = false;
            foreach ($Objects as $Object) {
                if (strcmp($Object->getName(), trim($tmp_Object)) == 0) {
                    $this->obj_array[] = &$Object;
                    $tmp_FoundObject = true;
                    break;
                }
            }
        }

    }

    protected function getElementMappingOffset()
    {
        return 2;
    }

    function enterObject()
    {
        return '';
    }

}
