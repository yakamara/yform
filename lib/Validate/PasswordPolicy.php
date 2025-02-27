<?php

namespace Yakamara\YForm\Validate;

use rex_i18n;
use rex_password_policy;

use function count;
use function is_array;

class PasswordPolicy extends AbstractValidate
{
    public const PASSWORD_POLICY_DEFAULT_RULES = '{"length":{"min":8},"letter":{"min":1},"lowercase":{"min":1},"uppercase":{"min":1},"digit":{"min":1},"symbol":{"min":1}}';

    public function enterObject()
    {
        $Object = $this->getValueObject();

        if (!$this->isObject($Object)) {
            return;
        }

        $rules = json_decode($this->getElement('rules'), true);
        if (!is_array($rules) || 0 == count($rules)) {
            $rules = json_decode(self::PASSWORD_POLICY_DEFAULT_RULES, true);
        }

        $PasswordPolicy = new rex_password_policy($rules);

        if ('' != $Object->getValue() && true !== $msg = $PasswordPolicy->check($Object->getValue())) {
            $this->params['warning'][$Object->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$Object->getId()] = '' == trim($this->getElement('message')) ? $msg : $this->getElement('message');
        }
    }

    public function getDescription(): string
    {
        return 'password_policy -> validate|password_policy|pswfield|warning_message|[config]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'validate',
            'name' => 'password_policy',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'message' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_defaults_message')],
                'rules' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_password_policy_rules'), 'notice' => rex_i18n::msg('yform_validate_password_policy_rules_notice', self::PASSWORD_POLICY_DEFAULT_RULES)],
            ],
            'description' => rex_i18n::msg('yform_validate_password_policy_description'),
        ];
    }
}
