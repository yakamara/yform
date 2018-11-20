<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_spamtimer extends rex_yform_value_abstract
{
    public function enterObject()
    {
        rex_login::startSession();

        if ($this->params['send'] == '1') {
            if(microtime(true) < $this->getElement('duration') + rex_session('yform_spamtimer','int',0)) {
                $this->params['warning_messages'][] = $this->getElement('message');
                rex_set_session('yform_spamtimer', microtime(true));
            } 
        }
        rex_set_session('yform_spamtimer', microtime(true));

        if ($this->needsOutput()) {
            $this->setName($this->getFieldName());
            $this->params['form_output'][$this->getId()] = '';
        }
    }

    public function getDescription()
    {
        return 'spamtimer|name|duration|warning_message';
    }


    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'spamtimer',
            'values' => [
                'name' => ['type' => 'text', 'default' => "spamtimer", 'notice' => rex_i18n::msg('yform_values_spamtimer_name_notice'), 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'duration' => ['type' => 'integer', 'default' => 5, 'notice' => rex_i18n::msg('yform_values_spamtimer_duration_notice'), 'label' => rex_i18n::msg('yform_values_spamtimer_duration')],
                'message' => ['type' => 'text', 'default' => rex_i18n::msg('yform_values_spamtimer_message_default'), 'label' => rex_i18n::msg('yform_values_spamtimer_message')],
            ],
            'description' => rex_i18n::msg('yform_values_spamtimer_description'),
            'db_type' => ['none'],
            'multi_edit' => 'always',
            'is_searchable' => false,
            'is_hiddeninlist' => true,
        ];
    }

}
