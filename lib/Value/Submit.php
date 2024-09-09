<?php

namespace Yakamara\YForm\Value;

use rex_i18n;

use function count;
use function in_array;

class Submit extends AbstractValue
{
    public function init()
    {
        $this->params['submit_btn_show'] = false;
    }

    public function enterObject()
    {
        $labels = $this->getElement('labels');
        if ('' == $labels) {
            $labels = [];
        } else {
            $labels = explode(',', $labels);
        }

        $values = $this->getElement('values');
        if ('' == $values) {
            $values = [];
        } else {
            $values = explode(',', $values);
        }

        $default_value = '';
        if ($this->getElement('default')) {
            $default_value = $this->getElement('default');
        }

        if (in_array($this->getValue(), $labels)) {
            $key = array_search($this->getValue(), $labels);
            if (isset($values[$key])) {
                $value = $values[$key];
            } else {
                $value = $default_value;
            }
        } else {
            $value = $default_value;
        }

        $this->setValue($value);

        if (0 == count($labels)) {
            $labels = [$value];
        }

        if (1 == count($labels) && '' == $this->getElement('css_classes')) {
            $this->setElement('css_classes', 'btn-primary');
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.submit.tpl.php', compact('labels'));
            }
        }

        if (!isset($this->params['value_pool']['email'][$this->getName()]) || '' == $this->params['value_pool']['email'][$this->getName()]) {
            $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        }

        if ($this->saveInDb() && 'no_db' != $this->getElement(3)) { // BC element[3]
            if (!isset($this->params['value_pool']['sql'][$this->getName()]) || '' == $this->params['value_pool']['sql'][$this->getName()]) {
                $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
            }
        }
    }

    public function getDescription(): string
    {
        return 'submit|name|labelvalue1_on_button1,labelvalue2_on_button2| [value_1_to_save_if_clicked,value_2_to_save_if_clicked] | [no_db] | [Default-Wert] | [cssclassname1,cssclassname2]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'submit',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'labels' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_submit_labels')],
                'values' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_submit_values')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_submit_default')],
                'css_classes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_submit_css_classes'),
                ],
            ],
            'description' => rex_i18n::msg('yform_values_submit_description'),
            'db_type' => ['text'],
            'is_searchable' => false,
            'is_hiddeninlist' => true,
        ];
    }
}
