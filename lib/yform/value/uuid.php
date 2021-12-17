<?php

/**
 * yform.
 *
 * @author mail[at]alexplus[dot]de Alexander Walther
 * @author <a href="https://www.alexplus.de">www.alexplus.de</a>
 */

class rex_yform_value_uuid extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if(!$this->getValue()) {
            $this->setValue(self::guidv4());
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public static function guidv4($data = null) {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(mb_strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function getDescription(): string
    {
        return 'uuid|name|[no_db]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'uuid',
            'values' => [
                'name' => ['type' => 'name',        'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',       'label' => rex_i18n::msg('yform_values_defaults_label')],
                'no_db' => ['type' => 'no_db',      'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
            ],
            'description' => rex_i18n::msg('yform_values_uuid_description'),
            'db_type' => ['varchar(36)'],
        ];
    }

    public static function getSearchField($params)
    {
        rex_yform_value_text::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return rex_yform_value_text::getSearchFilter($params);
    }

    public static function getListValue($params)
    {
        return rex_yform_value_text::getListValue($params);
    }
}
