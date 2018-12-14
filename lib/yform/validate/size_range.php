<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_size_range extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        $Object = $this->getValueObject();

        if (!$this->isObject($Object)) {
            return;
        }

        if ($Object->getValue() == '') {
            return;
        }

        $w = false;

        $minsize = -1;
        if ($this->getElement('min') != '') {
            $minsize = (int) $this->getElement('min');
        }

        $maxsize = -1;
        if ($this->getElement('max') != '') {
            $maxsize = (int) $this->getElement('max');
        }

        $size = strlen($Object->getValue());

        if ($minsize > -1 && $minsize > $size) {
            $w = true;
        }

        if ($maxsize > -1 && $maxsize < $size) {
            $w = true;
        }

        if ($w) {
            $this->params['warning'][$Object->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
        }
    }

    public function getDescription()
    {
        return 'validate|size_range|name|[minsize]|[maxsize]|Fehlermeldung';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'validate',
            'name' => 'size_range',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => 'Name'],
                'min' => ['type' => 'text', 'label' => 'Minimale Anzahl der Zeichen (opt)'],
                'max' => ['type' => 'text', 'label' => 'Maximale Anzahl der Zeichen (opt)'],
                'message' => ['type' => 'text', 'label' => 'Fehlermeldung'],
            ],
            'description' => rex_i18n::msg('yform_validate_size_range_description'),
        ];
    }
}
