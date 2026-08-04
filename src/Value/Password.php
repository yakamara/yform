<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;

/**
 * Class Password.
 */

#[AsValue('password')]
class Password extends Text
{
    public function enterObject()
    {
        $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
        $attributes['type'] = 'password';
        $this->setElement('attributes', json_encode($attributes));

        parent::enterObject();
    }

    public function getDescription(): string
    {
        return 'password|name|label|defaultwert|[no_db]|[attributes]|[notice]|[prepend]|[append]';
    }

    public function getDefinitions(): array
    {
        $return = parent::getDefinitions();
        $return['type'] = 'password';
        $return['description'] = I18n::msg('yform_values_password_description');
        return $return;
    }
}
