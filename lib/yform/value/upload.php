<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_upload extends rex_yform_value_abstract
{

    function enterObject()
    {

        $error = array();

        $rfile = 'file_' . md5($this->getFieldName('file'));

        $err_msgs = $this->getElement('messages'); // min_err,max_err,type_err,empty_err
        if (!is_array($err_msgs)) {
            $err_msgs = explode(',', $err_msgs);
        }

        $err_msgs['min_error']   = $err_msgs[0];
        $err_msgs['max_error']   = isset($err_msgs[1]) ? rex_i18n::translate($err_msgs[1]) : 'max_error';
        $err_msgs['type_error']  = isset($err_msgs[2]) ? rex_i18n::translate($err_msgs[2]) : 'type_error';
        $err_msgs['empty_error'] = isset($err_msgs[3]) ? rex_i18n::translate($err_msgs[3]) : 'empty_error';
        $err_msgs['delete_file'] = isset($err_msgs[4]) ? rex_i18n::translate($err_msgs[4]) : 'delete ';

        $this->tmp_messages = $err_msgs;

        $value = $this->getValue();
        if ($value == "") {
            $value = $this->getElement('default_file');
        }
        $this->setValue('');
        $value_email = '';
        $value_sql = '';

        if (!is_string($value) && $value["delete"] == 1) {
            $value = '';
        }

        // SIZE CHECK
        $sizes   = explode(',', $this->getElement('max_size'));
        $minsize = count($sizes) > 1 ? (int) ($sizes[0] * 1024) : 0;
        $maxsize = count($sizes) > 1 ? (int) ($sizes[1] * 1024) : (int) ($sizes[0] * 1024);
        if ( $this->params['send'] && isset($_FILES[$rfile]) && $_FILES[$rfile]['name'] != '' && ($_FILES[$rfile]['size'] > $maxsize || $_FILES[$rfile]['size'] < $minsize) ) {
            if ($_FILES[$rfile]['size'] < $minsize) {
                $error[] = $err_msgs['min_error'];
            }
            if ($_FILES[$rfile]['size'] > $maxsize) {
                $error[] = $err_msgs['max_error'];
            }
            unset($_FILES[$rfile]);
        }

        $database_filename_field = $this->getElement('database_filename_field');
        if ($database_filename_field != "") {
            $value = $this->params['value_pool']['sql'][$database_filename_field];
        }

        $prefix = md5(mt_rand().microtime(true)).'_';
        if ($this->getElement('file_prefix')) {
            $prefix .= $this->getElement('file_prefix').'_';
        }
        $upload_folder = $this->getElement('upload_folder');
        if ($upload_folder == "") {
            $upload_folder = rex_path::addonData('yform','uploads');
            rex_dir::create($upload_folder);
        }

        if ($value != "") {

            if (rex::isBackend()) {
                $value = explode("_",$value,2);
                $value = $value[0];
            }

            $search_path = $upload_folder.'/'.$value.'_'.$this->getElement('file_prefix');
            $files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $search_path).'*');

            if (count($files) == 1) {
                $value = basename($files[0]);

                if (rex_request("rex_upload_downloadfile") == $value) {
                  $file = $upload_folder.'/'.$value;
                  if (file_exists($file)) {
                    ob_end_clean();
                    $filename = explode("_",basename($file),2);
                    $filename = $filename[1];
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename='.$filename);
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file));
                    readfile($file);
                    exit;
                  }
                }

            } else {
                $value = "";

            }
        }


        if ($this->params['send']) {

            if (isset($_FILES[$rfile]) &&  $_FILES[$rfile]['name'] != '' ) {
                $FILE['size']     = $_FILES[$rfile]['size'];
                $FILE['name']     = $_FILES[$rfile]['name'];
                $FILE['type']     = $_FILES[$rfile]['type'];
                $FILE['tmp_name'] = $_FILES[$rfile]['tmp_name'];
                $FILE['error']    = $_FILES[$rfile]['error'];
                $FILE['name_normed'] = strtolower(preg_replace('/[^a-zA-Z0-9.\-\$\+]/', '_', $FILE['name']));

                $extensions_array = explode(',', $this->getElement('types'));
                $ext = '.' . pathinfo($FILE['name'], PATHINFO_EXTENSION);

                if (!in_array(strtolower($ext), $extensions_array) && !in_array(strtoupper($ext), $extensions_array)) {
                    $error[] = $err_msgs['type_error'];
                    $value = '';

                } else {
                    $file_normed = $FILE['name_normed'];
                    $file_normed_new = $prefix.$file_normed;
                    if (file_exists($upload_folder . '/' . $file_normed_new)) {
                      for ($cf = 1; $cf < 1000; $cf++) {
                        $file_normed_new = $prefix . $cf . '_' . $file_normed ;
                        if (!file_exists($upload_folder . '/' . $file_normed_new)) {
                          break;
                        }
                      }
                    }

                    $value = $file_normed_new;

                    if (!@move_uploaded_file($FILE['tmp_name'], $upload_folder . '/' . $file_normed_new ) ) {
                      if (!@copy($FILE['tmp_name'], $upload_folder . '/' . $file_normed_new )) {
                        $error[] = 'upload failed: destination folder problem';
                        $value = '';

                      } else {
                        @chmod($upload_folder . '/' . $file_normed_new, rex::getDirPerm());

                      }
                    } else {
                      @chmod($upload_folder . '/' . $file_normed_new, rex::getDirPerm());

                    }

                }

            }

        }

        if (count($error) == 0) {
            switch ($this->getElement('modus')) {
                case('database'):
                    if ($database_filename_field != "") {
                      $this->params['value_pool']['email'][$database_filename_field] = $value; // $FILE['name_normed'];
                      $this->params['value_pool']['sql'][$database_filename_field] = $value; // $FILE['name_normed'];
                    }

                    $value_email = file_get_contents($upload_folder.'/'.$value);
                    $value_sql = $value_email;
                    break;

                case('upload'):
                default:
                    $value_email = $value;
                    $value_sql = $value_email;
                    break;

            }

        }

        $this->setValue($value);
        $this->params['value_pool']['email'][$this->getName()] = $value_email;
        $this->params['value_pool']['sql'][$this->getName()] = $value_sql;

        ## check for required file
        if ($this->params['send'] && $this->getElement('required') == 1 && $this->getValue() == '') {
            $error[] = $err_msgs['empty_error'];
        }

        ## setting up error Message
        if ($this->params['send'] && count($error) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $error);
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.upload.tpl.php');

    }

    function getDescription()
    {
        return 'upload -> Beispiel: upload|name | label | '.
                'Maximale Größe in Kb oder Range 100,500 | '.
                'endungenmitpunktmitkommasepariert | '.
                'pflicht=1 | '.
                'min_err,max_err,type_err,empty_err,delete_file_msg | '.
                'Speichermodus(upload/database/no_save) | '.
                '`database`: Dateiname wird gespeichert in Feldnamen | '.
                'Eigener Uploadordner [optional] | '.
                'Dateiprefix [optional] |';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'upload',
            'values' => array(
                'name'     => array( 'type' => 'label',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'max_size' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_upload_max_size")),
                'types'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_upload_types")),
                'required' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_upload_required")),
                'messages' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_upload_messages")),
                'modus'    => array( 'type' => 'select',  'label' => rex_i18n::msg("yform_values_upload_modus"), 'options' => 'upload,database,no_save', 'default' => 'upload'),
                'database_filename_field'    => array( 'type' => 'text',  'label' => rex_i18n::msg("yform_values_upload_database_filename_field")),
                'upload_folder'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_upload_upload_folder")),
                'file_prefix'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_upload_file_prefix")),
                'default_file'  => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_upload_default_file"))
            ),
            'description' => rex_i18n::msg("yform_values_upload_description"),
            'dbtype' => 'blob'
        );
    }

    static function getListValue($params)
    {
        $return = '';
        $field = new rex_yform_manager_field($params['params']["field"]);

        if ($field->getElement('modus') == "database") {
            $return = '[raw data]';

        } else {

            $upload_folder = $field->getElement('upload_folder');
            if ($upload_folder == "") {
                $upload_folder = rex_path::addonData('yform','uploads');
            }

            $value = explode("_", $params['value'], 2);

            if (count($value) == 2) {
                $hash = $value[0];
                $value = $value[1];
                $search_path = $upload_folder.'/'.$hash.'_'.$field->getElement('file_prefix');
                $files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $search_path).'*');
                if (count($files) == 1) {
                    $return = '<a href="'.$_SERVER["REQUEST_URI"].'&rex_upload_downloadfile='.urlencode($params['value']).'">'.basename($value).'</a>';
                    if (rex_request("rex_upload_downloadfile") == $params['value']) {
                        $file = $upload_folder.'/'.$params['value'];
                        if (file_exists($file)) {
                            ob_end_clean();
                            $filename = explode("_",basename($file),2);
                            $filename = $filename[1];
                            header('Content-Description: File Transfer');
                            header('Content-Type: application/octet-stream');
                            header('Content-Disposition: attachment; filename='.$filename);
                            header('Expires: 0');
                            header('Cache-Control: must-revalidate');
                            header('Pragma: public');
                            header('Content-Length: ' . filesize($file));
                            readfile($file);
                        }
                    }

                }

            }

        }

        return $return;
    }

}
