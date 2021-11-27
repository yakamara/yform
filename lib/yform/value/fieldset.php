<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_fieldset extends rex_yform_value_abstract
{
    public static $fieldset_options = ['onlyclose', 'onlycloseall', 'onlyopen', 'closeandopen'];

    public function enterObject()
    {
        if (!$this->needsOutput() || !$this->isViewable()) {
            return;
        }

        $output = '';

        $option = $this->getElement('options');
        if (!in_array($option, self::$fieldset_options)) {
            $option = 'closeandopen';
        }

        $attributes = $this->getElement('attributes');
        $attributes = json_decode($attributes, true);

        // deprecated
        // BC yform < Version 4
        if ('' != $this->getElement('attributes') && !is_array($attributes)) {
            $attributes['class'] = $this->getElement('attributes');
        }

        $this->setElement('attributes', $attributes);

        switch ($option) {
            case 'closeandopen':
            case 'onlyclose':
                if ($this->params['fieldsets_opened'] > 0) {
                    $output .= $this->parse('value.fieldset.tpl.php', ['option' => 'close', 'attributes' => $attributes]);
                    --$this->params['fieldsets_opened'];
                }
                break;
            case 'onlycloseall':
                for ($i = 0; $i < $this->params['fieldsets_opened']; ++$i) {
                    $output .= $this->parse('value.fieldset.tpl.php', ['option' => 'close', 'attributes' => $attributes]);
                }
                $this->params['fieldsets_opened'] = 0;
                break;
            case 'onlyopen':
                break;
        }

        switch ($option) {
            case 'closeandopen':
            case 'onlyopen':
                $this->params['fieldsets_opened']++;
                $output .= $this->parse('value.fieldset.tpl.php', ['option' => 'open', 'attributes' => $attributes]);
                break;
        }

        $this->params['form_output'][$this->getId()] = $output;
    }

    public function getDescription(): string
    {
        return 'fieldset|name|label|[attributes]|[onlyclose/onlycloseall/onlyopen/closeandopen]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'fieldset',
            'values' => [
                'name' => ['type' => 'name',  'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',  'label' => rex_i18n::msg('yform_values_defaults_label')],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'options' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_defaults_options'), 'choices' => self::$fieldset_options, 'default' => 'onlyopen'],
            ],
            'description' => rex_i18n::msg('yform_values_fieldset_description'),
            'db_type' => ['none'],
            'is_searchable' => false,
            'is_hiddeninlist' => true,
            'multi_edit' => 'always',
        ];
    }
}
