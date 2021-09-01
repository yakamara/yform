<?php

/**
 * Class rex_yform_value_password.
 */

class rex_yform_value_password extends rex_yform_value_text
{
    public function enterObject()
    {
        $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
        $attributes['type'] = 'password';
        $this->setElement('attributes', json_encode($attributes));

        parent::enterObject();
    }

    public function getDescription()
    {
        return 'password|name|label|defaultwert|[no_db]|[attributes]|[notice]|[prepend]|[append]';
    }

    public function getDefinitions()
    {
        $return = parent::getDefinitions();
        $return['type'] = 'password';
        $return['description'] = rex_i18n::msg('yform_values_password_description');
        return $return;
    }
}
