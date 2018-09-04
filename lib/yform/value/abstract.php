<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class rex_yform_value_abstract extends rex_yform_base_abstract
{
    public $element_values = [];

    public $value;
    public $name;
    public $label;
    public $type;
    public $keys = [];

    // ------------

    public function setArticleId($aid)
    {
        $this->aid = $aid;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    //  ------------ keys

    public function getFieldId($k = '')
    {
        if ($k === '') {
            return 'yform-' . $this->params['form_name'] . '-field-' . $this->getId();
        }
        return 'yform-' . $this->params['form_name'] . '-field-' . $this->getId() . '_' . $k;
    }

    public function getFieldName($k = '')
    {
        return $this->params['this']->getFieldName($this->getId(), $k, $this->getName());
    }

    public function getHTMLId($suffix = '')
    {
        if ($suffix != '') {
            return 'yform-' . $this->params['form_name'] . '-' . $this->getName() . '-' . $suffix;
        }
        if ($this->getName() != '') {
            return 'yform-' . $this->params['form_name'] . '-' . $this->getName();
        }

        return '';
    }

    public function getHTMLClass()
    {
        return 'form' . $this->type;
    }

    // ------------ helpers

    public function setKey($k, $v)
    {
        $this->keys[$k] = $v;
    }

    public function getKeys()
    {
        return $this->keys;
    }

    public function getValueFromKey($v = '')
    {
        if ($v == '') {
            $v = $this->getValue();
        }

        if (is_array($v)) {
            return $v;
        }
        if (isset($this->keys[$v])) {
            return $this->keys[$v];
        }
        return $v;
    }

    public function emptyKeys()
    {
        $this->keys = [];
    }

    public function getArrayFromString($string)
    {
        if (is_array($string)) {
            return $string;
        }

        $delimeter = ',';
        $rawOptions = preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $string);

        $options = [];
        foreach ($rawOptions as $option) {
            $delimeter = '=';
            $finalOption = preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $option);
            $v = $finalOption[0];
            if (isset($finalOption[1])) {
                $k = $finalOption[1];
            } else {
                $k = $finalOption[0];
            }
            $s = ['\=', '\,'];
            $r = ['=', ','];
            $k = str_replace($s, $r, $k);
            $v = str_replace($s, $r, $v);
            $options[$k] = $v;
        }

        return $options;
    }

    public function needsOutput()
    {
        return $this->getParam('form_needs_output', true);
    }

    public function parse($template, $params = [])
    {
        extract($params);
        ob_start();
        include $this->params['this']->getTemplatePath($template);
        return ob_get_clean();
    }

    public function getAttributeElements(array $attributes, array $direct_attributes = [])
    {
        $attributes = self::getAttributeArray($attributes, $direct_attributes);
        $return = [];
        foreach ($attributes as $attribute => $value) {
            $return[] = $attribute.'="'.htmlspecialchars($value).'"';
        }

        return $return;
    }

    public function getAttributeArray(array $attributes, array $direct_attributes = [])
    {
        $additionalAttributes = $this->getElement('attributes');
        if ($additionalAttributes) {
            if (!is_array($additionalAttributes)) {
                $additionalAttributes = json_decode(trim($additionalAttributes), true);
            }
            if ($additionalAttributes && is_array($additionalAttributes)) {
                foreach ($additionalAttributes as $attribute => $value) {
                    $attributes[$attribute] = $value;
                }
            }
        }

        foreach ($direct_attributes as $attribute) {
            if (($element = $this->getElement($attribute))) {
                $attributes[$attribute] = $element;
            }
        }

        return $attributes;
    }

    public function getWarningClass()
    {
        if (isset($this->params['warning'][$this->getId()])) {
            return ' ' . $this->params['warning'][$this->getId()];
        }
        return '';
    }

    public function isValidationDisabled()
    {
        return $this->getElement('validation_disabled');
    }

    // ------------

    public function loadParams(&$params, $elements = [])
    {
        parent::loadParams($params, $elements);
        $this->setLabel($this->getElement(2));
        $this->setName($this->getElement(1));
        $this->type = $this->getElement(0);
    }

    protected function getElementMappingOffset()
    {
        return 1;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->getLabelStyle($this->label);
    }

    public function getLabelStyle($label)
    {
        $label = rex_i18n::translate($label, null);

        if ($this->params['form_label_type'] == 'html') {
        } else {
            $label = nl2br(htmlspecialchars($label));
        }
        return $label;
        return '<span style="color:#f90">' . ($label) . '</span>';
    }

    protected function getValueForKey($key)
    {
        if (isset($this->params['value_pool']['sql'][$key])) {
            return $this->params['value_pool']['sql'][$key];
        }
        if (isset($this->params['sql_object']) && $this->params['sql_object']->hasValue($key)) {
            return $this->params['sql_object']->getValue($key);
        }
        if (isset($this->params['rex_yform_set'][$key])) {
            return $this->params['rex_yform_set'][$key];
        }
        return null;
    }

    public function getDatabaseFieldTypes()
    {
        $definitions = $this->getDefinitions();
        $db_types = [];

        // deprecated
        if (isset($definitions['dbtype'])) {
            $definitions['db_type'] = [$definitions['dbtype']];
        }

        if (!isset($definitions['db_type'])) {
            $definitions['db_type'] = [];
        } else if (!is_array($definitions['db_type'])) {
            $definitions['db_type'] = [$definitions['db_type']];
        }
        foreach($definitions['db_type'] as $db_type){
            $db_types[$db_type] = $db_type;
        }
        return $db_types;
    }

    public function getDatabaseFieldDefaultType()
    {
        $db_types = $this->getDatabaseFieldTypes();
        reset($db_types);
        return key($db_types);
    }

    public function getDatabaseFieldNull()
    {
        $definitions = $this->getDefinitions();
        return (isset($definitions['db_null']) && $definitions['db_null']) ? true : false;
    }

    // ------------ Trigger

    public function enterObject()
    {
    }

    public function init()
    {
    }
}
