<?php

namespace Yakamara\YForm\Value;

use rex_i18n;

class Email extends AbstractValue
{
    public function enterObject()
    {
        $this->setValue((string) $this->getValue());

        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement(3));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.email.tpl.php', 'value.text.tpl.php'], ['type' => 'email']);
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
                $attributes['readonly'] = 'readonly';
                $this->setElement('attributes', json_encode($attributes));
                $this->params['form_output'][$this->getId()] = $this->parse(['value.email-view.tpl.php', 'value.text-view.tpl.php', 'value.view.tpl.php'], ['type' => 'email']);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.email.tpl.php', 'value.text.tpl.php'], ['type' => 'email']);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'email|name|label|defaultwert|[no_db]|[attributes]|[notice]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'email',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_email_default')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_email_description'),
            'db_type' => ['varchar(191)', 'text'],
            'famous' => false,
        ];
    }

    public static function getSearchField($params)
    {
        Text::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return Text::getSearchFilter($params);
    }

    public static function getListValue($params)
    {
        return Text::getListValue($params);
    }
}
