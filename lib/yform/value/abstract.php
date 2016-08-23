<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class rex_yform_value_abstract extends rex_yform_base_abstract
{
    var $element_values = array();

    var $value;
    var $name;
    var $label;
    var $type;
    var $keys = array();

    // ------------

    function setArticleId($aid)
    {
        $this->aid = $aid;
    }

    function setValue($value)
    {
        $this->value = $value;
    }

    function getValue()
    {
        return $this->value;
    }



    //  ------------ keys

    function getFieldId($k = '')
    {
        if ($k === '') {
            return 'yform-' . $this->params['form_name'] . '-field-' . $this->getId();
        }
        return 'yform-' . $this->params['form_name'] . '-field-' . $this->getId() . '_' . $k;
    }

    function getFieldName($k = '')
    {
        return $this->params['this']->getFieldName($this->getId(), $k, $this->getName());
    }

    function getHTMLId($suffix = '')
    {
        if ($suffix != '') {
            return 'yform-' . $this->params['form_name'] . '-' . $this->getName() . '-' . $suffix;
        } elseif ($this->getName() != '') {
            return 'yform-' . $this->params['form_name'] . '-' . $this->getName();
        }

        return '';
    }

    function getHTMLClass()
    {
        return 'form' . $this->type;
    }


    // ------------ helpers

    function setKey($k, $v)
    {
        $this->keys[$k] = $v;
    }

    function getKeys()
    {
        return $this->keys;
    }

    function getValueFromKey($v = '')
    {
        if ($v == '') {
            $v = $this->getValue();
        }

        if (is_array($v)) {
            return $v;

        } else {
            if (isset($this->keys[$v]))  {
                return $this->keys[$v];
            } else {
                return $v;
            }
        }
    }

    function emptyKeys()
    {
        $this->keys = array();
    }

    public function getArrayFromString($string)
    {
        if (is_array($string)) {
            return $string;

        } else {

            $delimeter = ",";
            $rawOptions = preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $string);

            $options = array();
            foreach ($rawOptions as $option) {

                $delimeter = "=";
                $finalOption = preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $option);
                $v = $finalOption[0];
                if (isset($finalOption[1])) {
                    $k = $finalOption[1];
                } else {
                    $k = $finalOption[0];
                }
                $s = array('\=','\,');
                $r = array('=',',');
                $k = str_replace($s, $r, $k);
                $v = str_replace($s, $r, $v);
                $options[$k] = $v;
            }

            return $options;

        }

    }

    function parse($template, $params = array())
    {
        extract($params);
        ob_start();
        include $this->params['this']->getTemplatePath($template);
        return ob_get_clean();
    }

    /* deprecated - will be deleted in Version 1.2 */
    function getAttributeElement($attribute, $boolean = false) {

        $element = $this->getElement($attribute);
        if ($element) {
            return ' ' . $attribute . '="' . ($boolean ? $attribute : htmlspecialchars($element)) . '"';
        }
        return '';
    }

    function getAttributeElements(array $attributes, array $direct_attributes = [])
    {
        $additionalAttributes = $this->getElement("attributes");
        if ($additionalAttributes) {
            if (!is_array($additionalAttributes)) {
                $additionalAttributes = json_decode(trim($additionalAttributes), true);
            }
            if ($additionalAttributes && is_array($additionalAttributes)) {
                foreach($additionalAttributes as $attribute => $value) {
                    $attributes[$attribute] = $value;
                }
            }
        }

        foreach($direct_attributes as $attribute) {
            if ( ($element = $this->getElement($attribute)) ) {
                $attributes[$attribute] = $element;
            }
        }

        $return = [];
        foreach($attributes as $attribute => $value) {
            $return[] = $attribute.'="'.htmlspecialchars($value).'"';
        }

        return $return;

    }

    function getWarningClass()
    {
        if (isset($this->params['warning'][$this->getId()])) {
            return ' ' . $this->params['warning'][$this->getId()];
        }
        return '';
    }

    // ------------

    function loadParams(&$params, $elements = array())
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

    function setName($name)
    {
        $this->name = $name;
    }

    function getName()
    {
        return $this->name;
    }

    function setLabel($label)
    {
        $this->label = $label;
    }

    function getLabel()
    {
        return $this->getLabelStyle($this->label);
    }

    function getLabelStyle($label)
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
        } elseif (isset($this->params['sql_object']) && $this->params['sql_object']->hasValue($key)) {
            return $this->params['sql_object']->getValue($key);
        } elseif (isset($this->params['rex_yform_set'][$key])) {
            return $this->params['rex_yform_set'][$key];
        }
        return null;
    }

    // ------------ Trigger

    function enterObject()
    {
    }

    function init()
    {
    }

}
