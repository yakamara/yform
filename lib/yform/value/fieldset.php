<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_fieldset extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if (!$this->needsOutput()) {
            return;
        }

        $output = '';

        $option = $this->getElement(4);
        $options = ['onlyclose', 'onlycloseall', 'onlyopen', 'closeandopen'];
        if (!in_array($option, $options)) {
            $option = 'closeandopen';
        }

        switch ($option) {
            case 'closeandopen':
            case 'onlyclose':
                if ($this->params['fieldsets_opened'] > 0) {
                    $output .= $this->parse('value.fieldset.tpl.php', ['option' => 'close']);
                    --$this->params['fieldsets_opened'];
                }
                break;
            case 'onlycloseall':
                for ($i = 0; $i < $this->params['fieldsets_opened']; ++$i) {
                    $output .= $this->parse('value.fieldset.tpl.php', ['option' => 'close']);
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
                $output .= $this->parse('value.fieldset.tpl.php', ['option' => 'open']);
                break;
        }

        $this->params['form_output'][$this->getId()] = $output;
    }

    public function getDescription()
    {
        return 'fieldset|name|label|[class]|[onlyclose/onlycloseall/onlyopen/closeandopen]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'fieldset',
            'values' => [
                'name' => ['type' => 'name',  'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',  'label' => rex_i18n::msg('yform_values_defaults_label')],
            ],
            'description' => rex_i18n::msg('yform_values_fieldset_description'),
            'db_type' => ['none'],
            'is_searchable' => false,
            'is_hiddeninlist' => true,
            'multi_edit' => 'always',
        ];
    }
}
