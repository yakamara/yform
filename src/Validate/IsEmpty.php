<?php

namespace Yakamara\YForm\Validate;

use Yakamara\YForm\Attribute\AsValidate;
use Redaxo\Core\Translation\I18n;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValidate('empty')]
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
                'name' => ['type' => 'select_names', 'multiple' => true, 'label' => I18n::msg('yform_validate_empty_name'), 'notice' => I18n::msg('yform_validate_empty_notices_name')],
                'message' => ['type' => 'text',        'label' => I18n::msg('yform_validate_empty_message'), 'notice' => I18n::msg('translatable')],
            ],
            'description' => I18n::msg('yform_validate_empty_description'),
            'famous' => true,
        ];
    }
}
