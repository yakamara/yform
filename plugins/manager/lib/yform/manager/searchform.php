<?php

class rex_yform_manager_searchform extends rex_yform
{
    public function getFieldName($id = '', $k = '', $label = '')
    {
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

    public function getFieldValue($id = '', $k = '', $label = '')
    {
        $label = $this->prepareLabel($label);
        $k = $this->prepareLabel($k);
        if ($label == '') {
            $label = $id;
        }
        if ($k == '' && isset($_REQUEST['rex_yform_searchvars'][$label])) {
            return $_REQUEST['rex_yform_searchvars'][$label];
        } elseif (isset($_REQUEST['rex_yform_searchvars'][$label][$k])) {
            return $_REQUEST['rex_yform_searchvars'][$label][$k];
        }
        return '';
    }

    public function setFieldValue($id = '', $value = '', $k = '', $label = '')
    {
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
