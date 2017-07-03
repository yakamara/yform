<?php

class rex_yform_value_google_geocode extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $values = explode(',', $this->getValue());
        $default = explode(',', $this->getElement('default'));

        if (count($values) == 2) {
            $valueLat = $this->google_geocode_floattostr($values[0]);
            $valueLng = $this->google_geocode_floattostr($values[1]);
        } elseif (count($default) == 2) {
            $valueLat = $this->google_geocode_floattostr($default[0]);
            $valueLng = $this->google_geocode_floattostr($default[1]);
        } else {
            $valueLat = $this->google_geocode_floattostr(0);
            $valueLng = $this->google_geocode_floattostr(0);
        }

        $value = $valueLat.','.$valueLng;

        $this->setValue($value);

        $mapWidth = '100%';
        if ($this->getElement('width') != '') {
            $mapWidth = $this->getElement('width');
        }
        $mapHeight = 300;
        if ($this->getElement('height') != '') {
            $mapHeight = $this->getElement('height');
        }

        $googleapikey = $this->getElement('googleapikey');

        $address = str_replace(' ', '', $this->getElement('address'));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();

        if (!$this->needsOutput()) {
            return;
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.text.tpl.php');
        $this->params['form_output'][$this->getId()] .= $this->parse(
            'value.google_geocode.tpl.php',
            compact('includeGoogleMaps', 'value', 'mapWidth', 'mapHeight', 'address', 'googleapikey')
        );
    }

    public function getDescription()
    {
        return 'google_geocode|gcode|Bezeichnung|pos_lat,pos_lng|strasse,plz,ort|width|height|';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'google_geocode',
            'values' => [
                'name' => ['type' => 'name',     'label' => 'Name'],
                'label' => ['type' => 'text',     'label' => 'Bezeichnung'],
                'address' => ['type' => 'text',     'label' => 'Names Positionsfindung'],
                'width' => ['type' => 'text',     'label' => 'Map-Breite'],
                'height' => ['type' => 'text',     'label' => 'Map-H&ouml;he'],
                'googleapikey' => ['type' => 'text',     'label' => 'Google-Api-Key'],
                'default' => ['type' => 'text',     'label' => 'Default', 'notice' => '0.000,0.000'],
            ],
            'description' => 'GoogeMap Positionierung',
            'dbtype' => 'text',
            'formbuilder' => false,
            'multi_edit' => false,
        ];
    }

    public function google_geocode_floattostr($val)
    {
        preg_match("#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o);
        return @$o[1] . sprintf('%d', @$o[2]) . (@$o[3] != '.' ? @$o[3] : '');
    }
}
