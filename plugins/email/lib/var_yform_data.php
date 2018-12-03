<?php

/**
 * REX_VALUE[1],.
 *
 * @package redaxo\structure\content
 */
class rex_var_yform_data extends rex_var
{
    protected function getOutput()
    {
        $field = $this->getArg('field', 0, true);
        if (!in_array($this->getContext(), ['yform_email_template'])) { // || !is_numeric($id) || $id < 1 || $id > 20
            return false;
        }

        if (!$value = $this->getContextData()[$field]) {
            $value = '';
        }

        if ($this->hasArg('isset') && $this->getArg('isset')) {
            return $value ? 'true' : 'false';
        }

        $output = $this->getArg('output');
        if ($output == 'plain' || $output == '') {
            $value = str_replace(['<?', '?>'], ['&lt;?', '?&gt;'], $value);
        } else {
            // $output = html
            $value = rex_escape($value);
            $value = nl2br($value);
        }

        return self::quote($value);
    }
}
