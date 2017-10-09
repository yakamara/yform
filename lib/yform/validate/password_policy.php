<?php

class rex_yform_validate_password_policy extends rex_yform_validate_abstract
{
    const PASSWORD_POLICY_DEFAULT_RULES = '{"length":{"min":8},"letter":{"min":1},"lowercase":{"min":1},"uppercase":{"min":1},"digit":{"min":1},"symbol":{"min":1}}';

    public function enterObject()
    {
        if ($this->params['send'] == '1') {
            $ValueObject = $this->getValueObject();

            $rules = json_decode($this->getElement('rules'), true);
            if (count($rules) == 0) {
                $rules = json_decode(self::PASSWORD_POLICY_DEFAULT_RULES, true);
            }

            $PasswordPolicy = new rex_password_policy($rules);

            if ($ValueObject->getValue() != '' && $PasswordPolicy->check($ValueObject->getValue()) !== true) {
                $this->params['warning'][$ValueObject->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$ValueObject->getId()] = $this->getElement('message');
            }
        }
    }

    public function getDescription()
    {
        return 'password_policy -> validate|password_policy|pswfield|warning_message|[config]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'validate',
            'name' => 'password_policy',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'message' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_defaults_message'), 'notice' => rex_i18n::msg('yform_validate_password_policy_message_notice')],
                'rules' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_password_policy_rules'), 'notice' => rex_i18n::msg('yform_validate_password_policy_rules_notice', self::PASSWORD_POLICY_DEFAULT_RULES)],
            ],
            'description' => rex_i18n::msg('yform_validate_password_policy_description'),
        ];
    }
}
