<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_textarea extends rex_yform_value_abstract
{

    function enterObject()
    {

        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        if ($this->getValue() == '' && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.textarea.tpl.php');

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

    }

    function getDescription()
    {
        return 'textarea -> Beispiel: textarea|name|label|default|[no_db]';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'textarea',
            'values' => array(
                'name'      => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'     => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'default'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_textarea_default")),
                'no_db'     => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
                'css_class' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_textarea_classes")),
				'notice'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_notice") ),
            ),
            'description' => rex_i18n::msg("yform_values_textarea_description"),
            'dbtype' => 'text',
            'famous' => true
        );
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', array('name' => $params['field']->getName(), 'label' => $params['field']->getLabel()));
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();
        $value = $params['value'];
        $field =  $params['field']->getName();

        if ($value == '(empty)') {
            return ' (' . $sql->escapeIdentifier($field) . ' = "" or ' . $sql->escapeIdentifier($field) . ' IS NULL) ';

        } elseif ($value == '!(empty)') {
            return ' (' . $sql->escapeIdentifier($field) . ' <> "" and ' . $sql->escapeIdentifier($field) . ' IS NOT NULL) ';

        }

        $pos = strpos($value, '*');
        if ($pos !== false) {
            $value = str_replace('%', '\%', $value);
            $value = str_replace('*', '%', $value);
            return $sql->escapeIdentifier($field) . " LIKE " . $sql->escape($value);
        } else {
            return $sql->escapeIdentifier($field) . " = " . $sql->escape($value);
        }

    }

}
