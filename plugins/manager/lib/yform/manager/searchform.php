<?php

class rex_yform_manager_searchform extends rex_yform
{
    public function getFieldName($label, array $params = [])
    {
        return 'rex_yform_searchvars[' . $label . ']';
        $label = $this->prepareLabel($label);
        $k = $this->prepareLabel($k);
        if ($label == '') {
            $label = $id;
        }

        if ($k == '') {
            return 'rex_yform_searchvars[' . $label . ']';
        }
        return 'rex_yform_searchvars[' . $label . '][' . $k . ']';
    }

    public function getFieldValue($label, array $params = []) // $id = '', $k = '', $label = ''
    {
        return '';
        $label = $this->prepareLabel($label);
        $k = $this->prepareLabel($k);
        if ($label == '') {
            $label = $id;
        }
        if ($k == '' && isset($_REQUEST['rex_yform_searchvars'][$label])) {
            return $_REQUEST['rex_yform_searchvars'][$label];
        }
        if (isset($_REQUEST['rex_yform_searchvars'][$label][$k])) {
            return $_REQUEST['rex_yform_searchvars'][$label][$k];
        }
        return '';
    }

    public function setFieldValue($label, array $params = []) //$id = '', $value = '', $k = '', $label = ''
    {
        return;
        $label = $this->prepareLabel($label);
        $k = $this->prepareLabel($k);
        if ($label == '') {
            $label = $id;
        }
        if ($k == '') {
            $_REQUEST['rex_yform_searchvars'][$label] = $value;
        } else {
            $_REQUEST['rex_yform_searchvars'][$label][$k] = $value;
        }
    }
}
