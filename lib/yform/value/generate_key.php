<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_generate_key extends rex_yform_value_abstract
{
    public function preValidateAction(): void
    {
        $generated_key = md5($this->params['form_name'].uniqid(random_int(0, getrandmax()), true));

        if (1 != $this->getElement('only_empty')) {
            // wird immer neu gesetzt
            $this->setValue($generated_key);
        } elseif ('' != $this->getValue()) {
            // wenn Wert vorhanden ist direkt zurück
        } elseif (isset($this->params['sql_object']) && '' != $this->params['sql_object']->getValue($this->getName())) {
            // sql object vorhanden und Wert gesetzt ?
        } else {
            $this->setValue($generated_key);
        }
    }

    public function enterObject()
    {
        if ($this->needsOutput() && 1 == $this->getElement('show_value')) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php');
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getValue() && $this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'generate_key|name|label|[no_db][0-always,1-only if empty,2-never]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'generate_key',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'only_empty' => ['type' => 'choice',  'label' => rex_i18n::msg('yform_values_generate_key_only_empty'), 'default' => '0', 'choices' => 'translate:yform_always=0,translate:yform_onlyifempty=1'],
                'show_value' => ['type' => 'checkbox',  'label' => rex_i18n::msg('yform_values_defaults_showvalue'), 'default' => '0', 'options' => '0,1'],
            ],
            'description' => rex_i18n::msg('yform_values_generate_key_description'),
            'db_type' => ['varchar(191)'],
            'multi_edit' => 'always',
        ];
    }
}
