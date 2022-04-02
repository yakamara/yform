<?php

class rex_yform_value_google_geocode extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $values = explode(',', $this->getValue());
        $default = explode(',', $this->getElement('default'));

        if (2 == count($values)) {
            $valueLat = $this->google_geocode_floattostr($values[0]);
            $valueLng = $this->google_geocode_floattostr($values[1]);
        } elseif (2 == count($default)) {
            $valueLat = $this->google_geocode_floattostr($default[0]);
            $valueLng = $this->google_geocode_floattostr($default[1]);
        } else {
            $valueLat = $this->google_geocode_floattostr(0);
            $valueLng = $this->google_geocode_floattostr(0);
        }

        $value = $valueLat.','.$valueLng;

        $this->setValue($value);

        $mapWidth = '100%';
        if ('' != $this->getElement('width')) {
            $mapWidth = $this->getElement('width');
        }
        $mapHeight = 300;
        if ('' != $this->getElement('height')) {
            $mapHeight = $this->getElement('height');
        }

        $mapZoom = 8;
        if ('' != $this->getElement('zoom')) {
            $mapZoom = $this->getElement('zoom');
        }

        $googleapikey = $this->getElement('googleapikey');

        $address = str_replace(' ', '', $this->getElement('address'));

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.google_geocode-view.tpl.php', 'value.view.tpl.php']);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.text.tpl.php');
                $this->params['form_output'][$this->getId()] .= $this->parse(
                    'value.google_geocode.tpl.php', compact('value', 'mapWidth', 'mapHeight', 'mapZoom', 'address', 'googleapikey')
                );
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDB()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'google_geocode|name|label|[street,zip,city]|width|height|googleapikey|zoom[1,5,10,15,20]|default';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'google_geocode',
            'values' => [
                'name' => ['type' => 'name',     'label' => 'Name'],
                'label' => ['type' => 'text',     'label' => 'Bezeichnung'],
                'address' => ['type' => 'text',     'label' => 'Adresse'],
                'width' => ['type' => 'text',     'label' => 'Kartenbreite'],
                'height' => ['type' => 'text',     'label' => 'Kartenh&ouml;he'],
                'googleapikey' => ['type' => 'text',     'label' => 'Google API Key'],
                'zoom' => ['type' => 'text',     'label' => 'Zoomstufe (Welt=1, Kontinent=5, Stadt=10, Straßen=15, Geb&auml;ude=20)'],
                'default' => ['type' => 'text',     'label' => 'Default', 'notice' => '0.000,0.000'],
                'infotext_1' => ['type' => 'text',     'label' => 'Text: Hole Position', 'default' => 'translate:yform_geo_get_position'],
                'infotext_2' => ['type' => 'text',     'label' => 'Text: Lösche Position', 'default' => 'translate:yform_geo_clear_position'],
            ],
            'description' => 'Google Maps Koordinaten',
            'db_type' => ['text'],
            'formbuilder' => false,
            'multi_edit' => false,
        ];
    }

    public function google_geocode_floattostr($val)
    {
        preg_match("#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o);
        return @$o[1] . sprintf('%d', @$o[2]) . ('.' != @$o[3] ? @$o[3] : '');
    }
}
