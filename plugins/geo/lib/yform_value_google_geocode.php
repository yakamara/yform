<?php

class rex_yform_value_google_geocode extends rex_yform_value_abstract
{

    function enterObject()
    {
        $labels = explode(',', $this->getElement('position')); // Fields of Position
        $labelLat = $labels[0];
        $labelLng = $labels[1];

        $valueLat = '0';
        $valueLng = '0';

        $mapWidth = "100%";
        if ($this->getElement('width') != '') {
            $mapWidth = (int) $this->getElement('width');
        }
        $mapHeight = 300;
        if ($this->getElement('height') != '') {
            $mapHeight = (int) $this->getElement('height');
        }

        foreach ($this->obj as $o) {
            if ($o->getName() == $labelLng) {
                $valueLng = $this->floattostr($o->getValue());
            }
            if ($o->getName() == $labelLat) {
                $valueLat = $this->floattostr($o->getValue());
            }
        }

        // Script nur beim ersten mal ausgeben
        $includeGoogleMaps = false;
        if (!defined('REX_XFORM_GOOGLE_GEOCODE_JSCRIPT')) {
            define('REX_XFORM_GOOGLE_GEOCODE_JSCRIPT', true);
            $includeGoogleMaps = true;
        }

        $address = str_replace(" ", "", $this->getElement('address'));

        $this->params['form_output'][$this->getId()] = $this->parse(
            'value.google_geocode.tpl.php',
            compact('includeGoogleMaps', 'labelLng', 'labelLat', 'valueLng', 'valueLat', 'mapWidth', 'mapHeight', 'address')
        );
    }

    function getDescription()
    {
        return 'google_geocode -> Beispiel: google_geocode|gcode|Bezeichnung|pos_lat,pos_lng|strasse,plz,ort|width|height|';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'google_geocode',
            'values' => array(
                'name'     => array( 'type' => 'name',     'label' => 'Name' ),
                'label'    => array( 'type' => 'text',     'label' => 'Bezeichnung'),
                'position' => array( 'type' => 'select_name', 'label' => '"lat"-name,"lng"-name'),
                'address'  => array( 'type' => 'select_name', 'label' => 'Names Positionsfindung'),
                'width'    => array( 'type' => 'text',     'label' => 'Map-Breite'),
                'height'   => array( 'type' => 'text',     'label' => 'Map-H&ouml;he'),
            ),
            'description' => 'GoogeMap Positionierung',
            'dbtype' => 'text'
        );

    }

    function floattostr( $val )
    {
        preg_match( "#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o );
        return @$o[1] . sprintf('%d', @$o[2]) . (@$o[3] != '.' ? @$o[3] : '');
    }

}
