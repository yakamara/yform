<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_nonce extends rex_yform_value_abstract
{
    public function enterObject()
    {
        rex_login::startSession();

        $key = $this->getElement('nonce_key');
        if ('' == $key) {
            $key = $this->params['form_name'];
        }
        $timeKey = ceil(time() / (86400 / 2)); // One Day from now key

        $toBeHashed = session_id().$key.$timeKey;

        if (rex::isBackend() && rex::getUser()) {
            $toBeHashed .= rex::getUser()->getId();
        }

        $hash = sha1($toBeHashed);
        $value = (string) $this->getValue();

        // validate
        if ($this->needsOutput() && '1' == $this->params['send']) {
            if ($value != $hash) {
                $this->params['warning'][$this->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$this->getId()] = $this->getElement('message');
            }
        }

        $this->setValue($hash);

        if ($this->needsOutput()) {
            $this->setName($this->getFieldName());
            $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
        }
    }

    public function getDescription()
    {
        return rex_escape('nonce|name|key|message]');
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'nonce',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'nonce_key' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_nonce_key'), 'notice' => rex_i18n::msg('yform_values_nonce_key_notice')],
                'message' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_defaults_message')],
            ],
            'description' => rex_i18n::msg('yform_values_nonce_description'),
            'dbtype' => 'none',
            'multi_edit' => 'always',
            'is_searchable' => false,
            'is_hiddeninlist' => true,
        ];
    }
}
