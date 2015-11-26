<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class rex_yform_validate_abstract extends rex_yform_base_abstract
{
    var $validateObjects = [];



    function getValueObjects($valueNames) {

        $Objects = [];
        $valueNames = explode(',', $valueNames);

        foreach ($valueNames as $valueName) {
            if ( ($Object == $this->getValueObject($valueName)) ) {
                $Objects[] = $Object;
            }
        }

        return $Objects;

    }

    function getValueObject($valueName = "") {

        if ($valueName == "") {
            $valueName = $this->getElement("name");
        }

        if ($valueName == "") {
            $valueName = $this->getElement(2);
        }

        foreach ($this->getObjects() as $Object) {
            if (strcmp($Object->getName(), trim($valueName)) == 0) {
                return $Object;
            }
        }

        return NULL;

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
