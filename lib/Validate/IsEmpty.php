<?php

namespace Yakamara\YForm\Validate;

use rex_i18n;

use function in_array;

class IsEmpty extends AbstractValidate
{
    public function enterObject()
    {
        $names = $this->getElement('name');
        if ('' == $names) {
            return;
        }

        $names = explode(',', $names);

        $warningObjects = [];
        foreach ($this->getObjects() as $Object) {
            if ($this->isObject($Object) && in_array($Object->getName(), $names)) {
                if ('' != $Object->getValue()) {
                    return;
                }
                $warningObjects[] = $Object;
            }
        }

        foreach ($warningObjects as $warningObject) {
            $this->params['warning'][$warningObject->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$warningObject->getId()] = $this->getElement('message');
        }
    }

    public function getDescription(): string
    {
        return 'validate|empty|name_1,name2,name3|warning_message ';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'validate',
            'name' => 'empty',
            'values' => [
                'name' => ['type' => 'select_names', 'multiple' => true, 'label' => rex_i18n::msg('yform_validate_empty_name'), 'notice' => rex_i18n::msg('yform_validate_empty_notices_name')],
                'message' => ['type' => 'text',        'label' => rex_i18n::msg('yform_validate_empty_message'), 'notice' => rex_i18n::msg('translatable')],
            ],
            'description' => rex_i18n::msg('yform_validate_empty_description'),
            'famous' => true,
        ];
    }
}
