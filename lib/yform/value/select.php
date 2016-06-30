<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_select extends rex_yform_value_abstract
{

    function enterObject()
    {
        $multiple = $this->getElement('multiple') == 1;

        $options = $this->getArrayFromString($this->getElement('options'));

        if ($multiple) {
            $size = (int) $this->getElement('size');
            if ($size < 2) {
                $size = count($options);
            }
        } else {
            $size = 1;
        }

        if (!$this->params['send'] && $this->getValue() == '' && $this->getElement('default') != '') {
            $this->setValue($this->getElement('default'));
        }

        // ---------- rex_yform_set
        if (isset($this->params['rex_yform_set'][$this->getName()]) && !is_array($this->params['rex_yform_set'][$this->getName()])) {
            $value = $this->params['rex_yform_set'][$this->getName()];
            $values = array();
            if (array_key_exists($value, $options)) {
                $values[] = $value;
            }
            $this->setValue($values);
            $this->setElement('disabled', true);
        }
        // ----------


        if (!is_array($this->getValue())) {
            $this->setValue(explode(',', $this->getValue()));
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.select.tpl.php', compact('options', 'multiple', 'size'));

        $this->setValue(implode(',', $this->getValue()));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['email'][$this->getName()."_NAME"] = isset($options[$this->getValue()]) ? $options[$this->getValue()] : null;

        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'select -> Beispiel: select|name|label|Frau=w,Herr=m|[no_db]|defaultwert|multiple=1|selectsize';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'select',
            'values' => array(
                'name'     => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'options'  => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_select_options")),
                'no_db'    => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),          'default' => 0),
                'default'  => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_select_deault")),
                'multiple' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_select_multiple")),
                'size'     => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_select_size")),
            ),
            'description' => rex_i18n::msg("yform_values_select_description"),
            'dbtype' => 'text'
        );

    }

    static function getListValue($params)
    {
        $return = array();

        $new_select = new self();
        $values = $new_select->getArrayFromString($params['params']['field']['options']);

        foreach (explode(',', $params['value']) as $k) {
            if (isset($values[$k])) {
                $return[] = rex_i18n::translate($values[$k]);
            }
        }

        return implode('<br />', $return);
    }

    public static function getSearchField($params)
    {
        $options = array();
        $options['(empty)'] = "(empty)";
        $options['!(empty)'] = "!(empty)";

        $new_select = new self();
        $options += $new_select->getArrayFromString($params['field']['options']);

        $params['searchForm']->setValueField('select', array(
                'name' => $params['field']->getName(),
                'label' => $params['field']->getLabel(),
                'options' => $options,
                'multiple' => 1,
                'size' => 5,
            )
        );
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();

        $field = $params['field']->getName();
        $values = (array) $params['value'];

        $where = array();
        foreach($values as $value) {
            switch($value){
                case("(empty)"):
                    $where[] = ' '.$sql->escapeIdentifier($field).' = ""';
                    break;
                case("!(empty)"):
                    $where[] = ' '.$sql->escapeIdentifier($field).' != ""';
                    break;
                default:
                    $where[] = ' ( FIND_IN_SET( ' . $sql->escape($value) . ', ' . $sql->escapeIdentifier($field) . ') )';
                    break;
            }
        }

        if (count($where) > 0) {
            return ' ( ' . implode(" or ", $where) . ' )';

        }

    }
}
