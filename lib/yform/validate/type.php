<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_type extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        if ($this->params['send'] == '1') {
            $Object = $this->getValueObject();

            if (!$this->isObject($Object)) {
                return;
            }

            if ($this->getElement('not_required') == 1 && $Object->getValue() == '') {
                return;
            }

            $w = false;

            switch (trim($this->getElement('type'))) {
                case 'int':
                case 'integer':
                    $xsRegEx_int = '/^[0-9]+$/i';
                    if (preg_match($xsRegEx_int, $Object->getValue()) == 0) {
                        $w = true;
                    }
                    break;
                case 'float':
                    $xsRegEx_float = "/^([0-9]+|([0-9]+\.[0-9]+))$/i";
                    if (preg_match($xsRegEx_float, $Object->getValue()) == 0) {
                        $w = true;
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($Object->getValue())) {
                        $w = true;
                    }
                    break;
                case 'string':
                    break;
                case 'email':
                    if (!preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/", $Object->getValue())) {
                        $w = true;
                    }
                    break;
                case 'url':
                    $xsRegEx_url = '/^(?:http[s]?:\/\/)[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:[a-zA-Z0-9][a-zA-Z0-9._-]*\.)*[a-zA-Z]{2,20}(?:\/[^\\/\:\*\?\"<>\|]*)*(?:\/[a-zA-Z0-9_%,\.\=\?\-#&]*)*$' . '/';
                    if (preg_match($xsRegEx_url, $Object->getValue()) == 0) {
                        $w = true;
                    }
                    break;
                case 'time':
                    $w = true;
                    $ex = explode(':', $Object->getValue());
                    if (count($ex) == 3 && $ex[0] > -839 && $ex[0] < 839 && $ex[1] >= 0 && $ex[1] < 60 && $ex[2] >= 0 && $ex[2] < 60) {
                        $w = false;
                    }
                    break;
                case 'date':
                    $w = true;
                    if (preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $Object->getValue(), $matches)) {
                        if (checkdate($matches[2], $matches[3], $matches[1])) {
                            $w = false;
                        }
                    }
                    break;
                case 'datetime':
                    $w = true;
                    if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $Object->getValue(), $matches)) {
                        if (checkdate($matches[2], $matches[3], $matches[1])) {
                            $w = false;
                        }
                    }
                    break;
                case 'hex':
                    $xsRegEx_hex = '/^[0-9a-fA-F]+$/i';
                    if (preg_match($xsRegEx_hex, $Object->getValue()) == 0) {
                        $w = true;
                    }
                    break;
                case '':
                    break;
                default:
                    echo 'Type ' . $this->getElement(3) . ' nicht definiert';
                    $w = true;
                    break;
            }

            if ($w) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
            }
        }
    }

    public function getDescription()
    {
        return 'validate|type|name|int/float/numeric/string/email/url/date/datetime|warning_messageg|[1=field not empty]';
    }

    public function getDefinitions()
    {
        return [
                'type' => 'validate',
                'name' => 'type',
                'values' => [
                    'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_type_name')],
                    'type' => ['type' => 'choice',    'label' => rex_i18n::msg('yform_validate_type_type'), 'choices' => 'int,float,numeric,string,email,url,date,datetime'],
                    'message' => ['type' => 'text',    'label' => rex_i18n::msg('yform_validate_type_message')],
                    'not_required' => ['type' => 'boolean',    'label' => rex_i18n::msg('yform_validate_type_not_required'), 'default' => 0],
                ],
                'description' => rex_i18n::msg('yform_validate_type_description'),
                'famous' => true,
            ];
    }
}
