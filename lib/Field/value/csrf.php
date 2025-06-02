<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_csrf extends rex_yform_value_abstract
{
    public function preValidateAction(): void
    {
        if ($this->params['csrf_protection']) {
            $tokenid = 'yform_' . $this->params['form_name'];

            $value = (string) $this->getValue();

            if ($this->needsOutput() && $this->params['send']) {
                if ($value != rex_csrf_token::factory($tokenid)->getValue()) {
                    $this->params['warning'][$this->getId()] = $this->params['error_class'];
                    $error_message = $this->getElement('message');
                    if ('' == $error_message) {
                        $error_message = $this->params['csrf_protection_error_message'];
                    }
                    if (rex::isBackend()) {
                        $this->params['warning_messages'][$this->getId()] = rex_i18n::msg('csrf_token_invalid');
                    } else {
                        $this->params['warning_messages'][$this->getId()] = $error_message;
                    }
                }
            }

            $this->setValue(rex_csrf_token::factory($tokenid)->getValue());

            if ($this->needsOutput()) {
                $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
            }
        }
    }

    public function getDescription(): string
    {
        return rex_escape('csrf|name|label|message');
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'csrf',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'message' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_defaults_message')],
            ],
            'description' => rex_i18n::msg('yform_values_csrf_description'),
            'db_type' => ['none'],
            'multi_edit' => 'always',
            'is_searchable' => false,
            'is_hiddeninlist' => true,
            'hidden' => true,
            'formbuilder' => false,
            'manager' => false,
        ];
    }
}
