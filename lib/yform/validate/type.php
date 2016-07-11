<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_type extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $Object = $this->getValueObject();

            if ($this->getElement('not_required') == 1 && $Object->getValue() == '') {
                return;
            }

            $w = false;

            switch (trim($this->getElement('type'))) {
                case 'int':
                    $xsRegEx_int = "/^[0-9]+$/i";
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
                    if (!preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/", $Object->getValue()) ) {
                        $w = true;
                    }
                    break;
                case 'url':
                    $xsRegEx_url = '/^(?:http:\/\/)[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:[a-zA-Z0-9][a-zA-Z0-9._-]*\.)*[a-zA-Z]{2,5}(?:\/[^\\/\:\*\?\"<>\|]*)*(?:\/[a-zA-Z0-9_%,\.\=\?\-#&]*)*$' . '/';
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
                case "hex":
                    $xsRegEx_hex = "/^[0-9a-fA-F]+$/i";
                    if (preg_match($xsRegEx_hex, $Object->getValue()) == 0)
                        $w = TRUE;
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


    function getDescription()
    {
        return 'type -> prüft auf typ,beispiel: validate|type|label|int(oder float/numeric/string/email/url/date/datetime)|Fehlermeldung|[1= Feld darf auch leer sein]';
    }

    function getDefinitions()
    {

            return array(
                'type' => 'validate',
                'name' => 'type',
                'values' => array(
                    'name'     => array( 'type' => 'select_name', 'label' => 'Name' ),
                    'type'     => array( 'type' => 'select',    'label' => 'Prüfung nach:', 'default' => '', 'options' => 'int,float,numeric,string,email,url,date,datetime' ),
                    'message'  => array( 'type' => 'text',    'label' => 'Fehlermeldung'),
                    'not_required' => array( 'type' => 'boolean',    'label' => 'Feld muss nicht ausgefüllt werden', 'default' => 0 ),
                ),
                'description' => rex_i18n::msg("yform_validate_type_description"),
                'famous' => true
            );

    }






}
