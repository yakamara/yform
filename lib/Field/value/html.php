<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_html extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if (!$this->needsOutput()) {
            return;
        }

        $html = $this->getElement('html');
        $label = $this->getElement('label');

        // BC
        if ('' == $html) {
            $html = $label;
        }

        $this->params['form_output'][$this->getId()] = $html;
    }

    public function getDescription(): string
    {
        return rex_escape('html|name|label|<div class="block"></div>');
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'html',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'html' => ['type' => 'textarea',    'attributes' => ['class' => 'form-control rex-code'], 'label' => rex_i18n::msg('yform_values_html_HTML')],
            ],
            'description' => rex_i18n::msg('yform_values_html_description'),
            'db_type' => ['none'],
            'multi_edit' => 'always',
            'is_searchable' => false,
            'is_hiddeninlist' => true,
        ];
    }
}
