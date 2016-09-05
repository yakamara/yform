<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_upload extends rex_yform_value_abstract
{

    public function enterObject()
    {

        $sid = session_id();
        if (empty($sid)) {
            session_start();
        }

        $upload_folder = self::upload_getFolder();
        $temp_folder = rex_path::pluginData('yform', 'manager', 'upload/temp');

        rex_dir::create($upload_folder);
        rex_dir::create($temp_folder);

        $error_messages = $this->getElement('messages'); // min_err,max_err,type_err,empty_err

        if (!is_array($error_messages)) {
            $error_messages = explode(',', $error_messages);
        }

        $error_messages['min_error']   = isset($error_messages[0]) ? rex_i18n::translate($error_messages[0]) : 'min_err';
        $error_messages['max_error']   = isset($error_messages[1]) ? rex_i18n::translate($error_messages[1]) : 'max_error';
        $error_messages['type_error']  = isset($error_messages[2]) ? rex_i18n::translate($error_messages[2]) : 'type_error';
        $error_messages['empty_error'] = isset($error_messages[3]) ? rex_i18n::translate($error_messages[3]) : 'empty_error';
        $error_messages['delete_file'] = isset($error_messages[4]) ? rex_i18n::translate($error_messages[4]) : 'delete ';
        $error_messages['destination_error'] = isset($error_messages[4]) ? rex_i18n::translate($error_messages[4]) : 'destination_error ';

        $formfieldkey = self::_upload_getFormFieldKey();

        $errors = [];
        $unique = self::_upload_getUniqueKey();

        if (!$this->params['send']) {
            $_SESSION[$formfieldkey] = [];
            $_SESSION[$formfieldkey]['unique'] = $unique;
            $_SESSION[$formfieldkey][$unique]['value'] = (string) $this->getValue();

        } else {

            $unique = $this->params["this"]->getFieldValue($this->getId(), 'unique');
            if ($unique == "") {
                self::_upload_getUniqueKey();

            }

            $delete = (boolean) @$this->params["this"]->getFieldValue($this->getId(), 'delete');
            if ($delete) {
                unset($_FILES[$unique]);
                unset($_SESSION[$formfieldkey][$unique]);

            }

            if (isset($_FILES[$unique]) && $_FILES[$unique]['name'] != '' ) {

                $FILE['size']     = $_FILES[$unique]['size'];
                $FILE['name']     = $_FILES[$unique]['name'];
                $FILE['name']     = strtolower(preg_replace('/[^a-zA-Z0-9.\-\$\+]/', '_', $FILE['name']));
                $FILE['type']     = $_FILES[$unique]['type'];
                $FILE['error']    = $_FILES[$unique]['error'];
                $FILE['tmp_name'] = $_FILES[$unique]['tmp_name'];
                $FILE['tmp_yform_name'] = $temp_folder . '/' . $unique . '_' . $this->getId() . '_' . $FILE['name'];
                $FILE['upload_folder'] = $upload_folder;
                $FILE['upload_name'] = $unique.'_'.$FILE['name']; // default_name

                unset($_FILES[$unique]);

                $extensions_array = explode(',', $this->getElement('types'));
                $ext = '.' . pathinfo($FILE['name'], PATHINFO_EXTENSION);

                if (
                    ( $this->getElement('types') != "" ) &&
                    ( !in_array(strtolower($ext), $extensions_array) && !in_array(strtoupper($ext), $extensions_array) )
                ) {
                    $error[] = $error_messages['type_error'];
                    unset($FILE);

                }

                if (isset($FILE)) {
                    $sizes   = explode(',', $this->getElement('sizes'));
                    $min_size = count($sizes) > 1 ? (int) ($sizes[0] * 1024) : 0;
                    $max_size = count($sizes) > 1 ? (int) ($sizes[1] * 1024) : (int) ($sizes[0] * 1024);

                    if ( $this->getElement('sizes') != "" && $FILE['size'] > $max_size ) {
                        $errors[] = $error_messages['min_error'];
                        unset($FILE);

                    } else if ( $this->getElement('sizes') != "" && $FILE['size'] < $min_size ) {
                        $errors[] = $error_messages['max_error'];
                        unset($FILE);

                    }

                }

                if (isset($FILE)) {
                    if (!@move_uploaded_file($FILE['tmp_name'], $FILE['tmp_yform_name'] ) ) {
                        if (!@copy($FILE['tmp_name'], $FILE['tmp_yform_name'] )) {
                            $error[] = 'upload failed: destination folder problem';
                            unset($FILE);

                        } else {
                            @chmod($FILE['tmp_yform_name'], rex::getFilePerm());

                        }

                    }

                }

                if (isset($FILE)) {
                    $_SESSION[$formfieldkey] = [];
                    $_SESSION[$formfieldkey][$unique]['file'] = $FILE;

                }

            }

        }

        $filename = "";
        $filepath = "";

        if (isset($_SESSION[$formfieldkey][$unique]['value'])) {
            $filename = (string) $_SESSION[$formfieldkey][$unique]['value'];
            $filepath = (string) $this->upload_getFolder().'/'.$this->getParam('main_id').'_'.$filename;
        }

        if (isset($_SESSION[$formfieldkey][$unique]['file'])) {

            $FILE = $_SESSION[$formfieldkey][$unique]['file'];
            if ($FILE['tmp_yform_name'] == "" || !file_exists($FILE['tmp_yform_name'])) {
                unset($_SESSION[$formfieldkey][$unique]['file']);
            } else {
                $filename = $FILE["name"];
            }

            $filepath = ['name'=>$FILE["name"], 'path' => $FILE['tmp_yform_name']];

        }

        if (rex::isBackend() && (rex_request("rex_upload_downloadfile", "string") == $this->getName()) ) {
            $this->upload_checkdownloadFile($filename, $filepath);
        }

        $this->setValue($filename);

        $this->params['value_pool']['email'][$this->getName()] = $filename;
        $this->params['value_pool']['email'][$this->getName().'_folder'] = $filename;
        $this->params['value_pool']['sql'][$this->getName()] = $filename;

        if ($filepath != "") {
            $this->params['value_pool']['files'][$this->getName()] = [$filename, $filepath];

        }

        if ($this->params['send'] && $this->getElement('required') == 1 && $filename == "") {
            $error[] = $error_messages['empty_error'];
        }

        if ($this->params['send'] && count($errors) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $errors);
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.upload.tpl.php', ['formfieldkey' => $formfieldkey, 'unique' => $unique, 'filename' => $filename, 'error_messages' => $error_messages]);

        return $this;
    }

    private function _upload_getFormFieldKey()
    {
        return 'upload_' . $this->getParam('form_name'). '_' . sha1($this->getFieldName('file'));

    }

    private function _upload_getUniqueKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(32, $cstrong));
    }

    public function upload_getFolder()
    {
        $folders = [];

        if ($this->getParam('main_table') != "") {
            $folders[] = $this->getParam('main_table');
        }

        if ($this->getName() != "") {
            $folders[] = $this->getName();
        }

        if (count($folders) == 0) {
            $folders[] = "frontend";
        }

        return rex_path::pluginData('yform', 'manager', 'upload/'.implode("/",$folders));

    }

    static function upload_checkdownloadFile($filename, $filepath)
    {
        if (file_exists($filepath)) {
            ob_end_clean();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.$filename);
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }

    }


    function postAction()
    {

        $unique = $this->params["this"]->getFieldValue($this->getId(), 'unique');
        $formfieldkey = $this->_upload_getFormFieldKey();

        if (isset($_SESSION[$formfieldkey][$unique]['file'])) {
            $FILE = $_SESSION[$formfieldkey][$unique]['file'];

            if (file_exists($FILE['tmp_yform_name'])) {

                $main_id = $this->getParam('main_id');
                if ($main_id != "") {
                    $FILE['upload_name'] = $main_id.'_'.$FILE['name'];

                }

                $upload_filefolder = $FILE['upload_folder'].'/'.$FILE['upload_name'];

                if (!move_uploaded_file($FILE['tmp_yform_name'], $upload_filefolder ) ) {
                    if (!copy($FILE['tmp_yform_name'], $upload_filefolder )) {
                        echo 'Uploadproblem: Code-YForm-Upload-Target';
                    } else {
                        chmod($upload_filefolder, rex::getFilePerm());

                    }

                } else {
                    chmod($upload_filefolder, rex::getFilePerm());

                }

            }

        }

        // delete temp files from this formfield
        $temp_folder = rex_path::pluginData('yform', 'manager', 'upload/temp');
        foreach (glob($temp_folder .'/'.$unique.'*') as $f) {
            unlink($f);
        }

        // delete old files from cache
        $cu = date("U");
        $offset = (60*60*3); // 3 hours
        foreach (glob($temp_folder .'/*') as $f) {
            $fu = date("U", filectime($f));
            if (($cu-$fu) > $offset) {
                unlink($f);
            }
        }

        unset($_SESSION[$formfieldkey][$unique]);

        parent::postAction();
    }

    function getDescription()
    {
        return 'upload -> Beispiel: upload|name | label | '.
        'Maximale Größe in Kb oder Range 100,500 oder leer lassen| '.
        'endungenmitpunktmitkommasepariert oder leer lassen| '.
        'pflicht=1 | '.
        'min_err,max_err,type_err,empty_err,delete_file_msg ';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'upload',
            'values' => array(
                'name'     => array( 'type' => 'name',	  'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'sizes'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_upload_sizes")),
                'types'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_upload_types")),
                'required' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_upload_required")),
                'messages' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_upload_messages")),
                'notice'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_notice")),
            ),
            'description' => rex_i18n::msg("yform_values_upload_description"),
            'dbtype' => 'text',
            'multi_edit' => false,
        );
    }

    static function getListValue($params)
    {
        $return = $params['value'];
        if (rex::isBackend()) {

            $field = new rex_yform_manager_field($params['params']["field"]);
            if ($params['value'] != "") {
                $return = '<a href="/redaxo/index.php?page=yform/manager/data_edit&table_name='.$field->getElement("table_name").'&data_id='.$params["list"]->getValue("id").'&func=edit&rex_upload_downloadfile='.urlencode($field->getElement("name")).'">'.$params["value"].'</a>';
            }

        }

        return $return;

    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', array('name' => $params['field']->getName(), 'label' => $params['field']->getLabel()));
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();
        $value = $params['value'];
        $field =  $params['field']->getName();

        if ($value == '(empty)') {
            return ' (' . $sql->escapeIdentifier($field) . ' = "" or ' . $sql->escapeIdentifier($field) . ' IS NULL) ';

        } elseif ($value == '!(empty)') {
            return ' (' . $sql->escapeIdentifier($field) . ' <> "" and ' . $sql->escapeIdentifier($field) . ' IS NOT NULL) ';

        }

        $pos = strpos($value, '*');
        if ($pos !== false) {
            $value = str_replace('%', '\%', $value);
            $value = str_replace('*', '%', $value);
            return $sql->escapeIdentifier($field) . " LIKE " . $sql->escape($value);
        } else {
            return $sql->escapeIdentifier($field) . " = " . $sql->escape($value);
        }

    }
}
