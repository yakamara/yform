<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_submits extends rex_yform_value_abstract
{

    function init()
    {
        $this->params['submit_btn_show'] = false;
    }

    function enterObject()
    {

        $labels = explode(",", $this->getElement('labels'));
        $values = $this->getElement('values');
        if ($values == "") {
          $values = array();
        } else {
          $values = explode(",", $values);
        }
        $default_value = $this->getElement('default');

        if (!in_array($this->getValue(), $labels)) {
            $this->setValue($default_value);
        } else {
            $key = array_search($this->getValue(), $labels);
            if ($key !== FALSE) {
                $this->setValue($values[$key]);
            }
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.submits.tpl.php');

        if (!isset($this->params['value_pool']['email'][$this->getName()]) || $this->params['value_pool']['email'][$this->getName()] == "") {
            $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        }

        if ($this->getElement('no_db') != 'no_db') {
            if (!isset($this->params['value_pool']['sql'][$this->getName()]) || $this->params['value_pool']['sql'][$this->getName()] == "") {
                $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
            }
        }

    }

    function getDescription()
    {
        return 'submits -> Beispiel: submit|label|labelvalue1_on_button1,labelvalue2_on_button2 | [value_1_to_save_if_clicked,value_2_to_save_if_clicked] | [no_db] | [Default-Wert] | [cssclassname1,cssclassname2]';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'submits',
            'values' => array(
                'name'      => array( 'type' => 'name',    'label' => rex_i18n::msg("yform_values_defaults_name")),
                'labels'     => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'values'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_submits_values")),
                'no_db'     => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
                'default'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_submits_default")),
                'css_classes' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_submits_css_classes")),
            ),
            'description' => rex_i18n::msg("yform_values_submits_description"),
            'dbtype' => 'text',
            'search' => false,
            'list_hidden' => true,
            'famous' => true
        );

    }

}
