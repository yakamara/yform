<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_upload extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $sessionFieldTimeout = 3600; // seconds
        if (isset($_SESSION['yform_field_upload'])) {
            foreach ($_SESSION['yform_field_upload'] as $unique => $session) {
                if (isset($session['stamp']) && $session['stamp'] < (date('U') - $sessionFieldTimeout)) {
                    unset($_SESSION['yform_field_upload'][$unique]);
                    // TODO: Datei aus tmp Ordner löschen ?
                }
            }
        }

        $upload_folder = self::upload_getFolder();
        $temp_folder = rex_path::pluginData('yform', 'manager', 'upload/temp');

        rex_dir::create($upload_folder);
        rex_dir::create($temp_folder);

        $error_messages = $this->getElement('messages'); // min_err,max_err,type_err,empty_err

        if (!is_array($error_messages)) {
            $error_messages = explode(',', $error_messages);
        }

        $error_messages['min_error'] = isset($error_messages[0]) ? rex_i18n::translate($error_messages[0]) : 'min_err';
        $error_messages['max_error'] = isset($error_messages[1]) ? rex_i18n::translate($error_messages[1]) : 'max_error';
        $error_messages['type_error'] = isset($error_messages[2]) ? rex_i18n::translate($error_messages[2]) : 'type_error';
        $error_messages['empty_error'] = isset($error_messages[3]) ? rex_i18n::translate($error_messages[3]) : 'empty_error';
        $error_messages['delete_file'] = isset($error_messages[4]) ? rex_i18n::translate($error_messages[4]) : 'delete ';
        $error_messages['destination_error'] = isset($error_messages[4]) ? rex_i18n::translate($error_messages[4]) : 'destination_error ';

        $errors = [];

        rex_login::startSession();

        $unique = $this->params['this']->getFieldValue($this->getName(), [$this->getId(), 'unique']);

        if ($unique == '') {
            // Nein - also anlegen
            $unique = self::_upload_getUniqueKey();
            $_SESSION['yform_field_upload'][$unique] = [];
            $_SESSION['yform_field_upload'][$unique]['stamp'] = date('U');
        }

        $delete = (bool) @$this->params['this']->getFieldValue($this->getName(), [$this->getId(), 'delete']);

        if ($delete) {
            unset($_FILES[$unique]);
            unset($_SESSION['yform_field_upload'][$unique]);
        }

        if (!$this->params['send']) {
            // Erster Aufruf. Ist File vorhanden ? dann Dateinamen setzen.
            $_SESSION['yform_field_upload'][$unique]['value'] = (string) $this->getValue();
            $_SESSION['yform_field_upload'][$unique]['stamp'] = date('U');
        }

        // Datei wurde hochgeladen - mit dem entsprechenden UniqueKey
        if (isset($_FILES[$unique]) && $_FILES[$unique]['name'] != '') {
            $FILE['size'] = $_FILES[$unique]['size'];
            $FILE['name'] = $_FILES[$unique]['name'];
            $FILE['name'] = mb_strtolower(preg_replace('/[^a-zA-Z0-9.\-\$\+]/', '_', $FILE['name']));
            $FILE['type'] = $_FILES[$unique]['type'];
            $FILE['error'] = $_FILES[$unique]['error'];
            $FILE['tmp_name'] = $_FILES[$unique]['tmp_name'];
            $FILE['tmp_yform_name'] = $temp_folder . '/' . $unique . '_' . $this->getId() . '_' . $FILE['name'];
            $FILE['upload_folder'] = $upload_folder;
            $FILE['upload_name'] = $unique.'_'.$FILE['name']; // default_name

            unset($_FILES[$unique]);

            $extensions_array = explode(',', $this->getElement('types'));
            $ext = '.' . pathinfo($FILE['name'], PATHINFO_EXTENSION);

            if (
                ($this->getElement('types') != '*') &&
                (!in_array(mb_strtolower($ext), $extensions_array) && !in_array(mb_strtoupper($ext), $extensions_array))
            ) {
                $errors[] = $error_messages['type_error'];
                unset($FILE);
            }

            if (isset($FILE)) {
                $sizes = explode(',', $this->getElement('sizes'));
                $min_size = count($sizes) > 1 ? (int) ($sizes[0] * 1024) : 0;
                $max_size = count($sizes) > 1 ? (int) ($sizes[1] * 1024) : (int) ($sizes[0] * 1024);

                if ($this->getElement('sizes') != '' && $FILE['size'] > $max_size) {
                    $errors[] = $error_messages['max_error'];
                    unset($FILE);
                } elseif ($this->getElement('sizes') != '' && $FILE['size'] < $min_size) {
                    $errors[] = $error_messages['min_error'];
                    unset($FILE);
                }
            }

            if (isset($FILE)) {
                if (!@move_uploaded_file($FILE['tmp_name'], $FILE['tmp_yform_name'])) {
                    if (!@copy($FILE['tmp_name'], $FILE['tmp_yform_name'])) {
                        $error[] = 'upload failed: destination folder problem';
                        unset($FILE);
                    } else {
                        @chmod($FILE['tmp_yform_name'], rex::getFilePerm());
                    }
                }
            }

            if (isset($FILE)) {
                // Datei wurde hochgeladen und wird zum speichern in der DB vorgemerkt
                $_SESSION['yform_field_upload'][$unique] = [];
                $_SESSION['yform_field_upload'][$unique]['file'] = $FILE;
                $_SESSION['yform_field_upload'][$unique]['stamp'] = date('U');
            }
        }

        $filename = '';
        $filepath = '';
        $real_filepath = '';
        $download_link = '';

        // Datei war bereits vorhanden - vorbereitung für den Download und setzen des Values
        if (isset($_SESSION['yform_field_upload'][$unique]['value'])) {
            $filename = (string) $_SESSION['yform_field_upload'][$unique]['value'];
            $filepath = (string) $this->upload_getFolder() . '/' . $this->getParam('main_id') . '_' . $filename;
            $real_filepath = $filepath;
        }

        // Datei aus Upload vorhanden - aber noch nicht gespeichert - vorbereitung für den Download und setzen des Values
        if (isset($_SESSION['yform_field_upload'][$unique]['file'])) {
            $FILE = $_SESSION['yform_field_upload'][$unique]['file'];
            if ($FILE['tmp_yform_name'] == '' || !file_exists($FILE['tmp_yform_name'])) {
                unset($_SESSION['yform_field_upload'][$unique]['file']);
            } else {
                $filepath = $FILE['tmp_yform_name'];
                $filename = $FILE['name'];
                $real_filepath = $FILE['upload_folder'].'/'.$FILE['upload_name'];
            }
        }

        /*
        if (rex::isBackend()) {

            $link_params = [];
            $link_params['page'] = 'yform/manager/data_edit';
            $link_params['table_name'] = rex_request('table_name','string');
            $link_params['func'] = rex_request('func', 'string');

            if ($this->getParam('main_id') != "") {
                $link_params['data_id'] = $this->getParam('main_id');
            }

            $link_params[$this->getFieldName('unique')] = $unique;
            $link_params['rex_upload_download'] = $this->getName();

            $download_link = '/redaxo/index.php?'.http_build_query($link_params);

        }
        */

        // Download starten - wenn Dateinamen übereinstimmen
        if (rex::isBackend() && (rex_request('rex_upload_downloadfile', 'string') == $this->getName()) && $filename != '' && $filepath != '') {
            $this->upload_checkdownloadFile($filename, $filepath);
        }

        // billiger hack, damit bei yorm save(), der wert nicht gelöscht wird
        if (!$delete && $this->params['send'] && $this->getValue() != '' && is_string($this->getValue()) && (!isset($_SESSION['yform_field_upload'][$unique]['file']) || $_SESSION['yform_field_upload'][$unique]['file'] == '')) {
            $filename = $this->getValue();
        }

        $this->setValue($filename);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['email'][$this->getName().'_folder'] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if ($filepath != '') {
            $this->params['value_pool']['files'][$this->getName()] = [$filename, $filepath, $real_filepath];
        }

        if ($this->params['send'] && $this->getElement('required') == 1 && $filename == '') {
            $errors[] = $error_messages['empty_error'];
        }

        if ($this->params['send'] && count($errors) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $errors);
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.upload.tpl.php', ['unique' => $unique, 'filename' => $filename, 'error_messages' => $error_messages, 'download_link' => $download_link]);
        }

        return $this;
    }

    private function _upload_getUniqueKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(32, $cstrong));
    }

    public function upload_getFolder()
    {
        $folders = [];

        if ($this->getParam('main_table') != '') {
            $folders[] = $this->getParam('main_table');
            if ($this->getName() != '') {
                $folders[] = $this->getName();
            }
        }

        if (count($folders) == 0) {
            $folders[] = 'frontend';
        }

        return rex_path::pluginData('yform', 'manager', 'upload/'.implode('/', $folders));
    }

    public static function upload_checkdownloadFile($filename, $filepath)
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

    public function postAction()
    {
        $unique = $this->params['this']->getFieldValue($this->getName(), [$this->getId(), 'unique']);

        if (isset($_SESSION['yform_field_upload'][$unique]['file'])) {
            $FILE = $_SESSION['yform_field_upload'][$unique]['file'];

            if (file_exists($FILE['tmp_yform_name'])) {
                $main_id = $this->getParam('main_id');
                if ($main_id != '' && $main_id != -1) {
                    $FILE['upload_name'] = $main_id.'_'.$FILE['name'];
                } else {
                    $FILE['upload_name'] = $unique.'_'.$FILE['name'];
                }

                $upload_filefolder = $FILE['upload_folder'].'/'.$FILE['upload_name'];

                if (!move_uploaded_file($FILE['tmp_yform_name'], $upload_filefolder)) {
                    if (!copy($FILE['tmp_yform_name'], $upload_filefolder)) {
                        echo 'Uploadproblem: Code-YForm-Upload-Target';
                    } else {
                        chmod($upload_filefolder, rex::getFilePerm());
                        $_SESSION['yform_field_upload'][$unique]['value'] = $FILE['name'];
                        $_SESSION['yform_field_upload'][$unique]['stamp'] = date('U');
                    }
                } else {
                    chmod($upload_filefolder, rex::getFilePerm());
                    $_SESSION['yform_field_upload'][$unique]['value'] = $FILE['name'];
                    $_SESSION['yform_field_upload'][$unique]['stamp'] = date('U');
                }
            }
        }

        unset($_SESSION['yform_field_upload'][$unique]['file']);

        // delete temp files from this formfield
        $temp_folder = rex_path::pluginData('yform', 'manager', 'upload/temp');
        foreach (glob($temp_folder .'/'.$unique.'*') as $f) {
            unlink($f);
        }

        // delete old files from cache
        $cu = date('U');
        $offset = (60 * 60 * 3); // 3 hours
        $dir = $temp_folder.'/';
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                $f = $dir.$file;
                $fu = date('U', filectime($f));
                if (($cu - $fu) > $offset && $file != '.' && $file != '..') {
                    unlink($f);
                }
            }
        }

        parent::postAction();
    }

    public function getDescription()
    {
        return 'upload|name|label|Maximale Größe in Kb oder Range 100,500 oder leer lassen| endungenmitpunktmitkommasepariert oder *| pflicht=1 | min_err,max_err,type_err,empty_err,delete_file_msg ';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'upload',
            'values' => [
                'name' => ['type' => 'name',      'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'sizes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_sizes')],
                'types' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_types')],
                'required' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_upload_required')],
                'messages' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_messages')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_upload_description'),
            'db_type' => ['text'],
            'multi_edit' => true,
        ];
    }

    public static function getListValue($params)
    {
        $value = $params['subject'];
        $length = strlen($value);
        $title = $value;
        if ($length > 30) {
            $value = mb_substr($value, 0, 15).' ... '.mb_substr($value, -15);
        }

        $return = $value;
        if (rex::isBackend()) {
            $field = new rex_yform_manager_field($params['params']['field']);
            if ($value != '') {
                $return = '<a href="/redaxo/index.php?page=yform/manager/data_edit&table_name='.$field->getElement('table_name').'&data_id='.$params['list']->getValue('id').'&func=edit&rex_upload_downloadfile='.urlencode($field->getElement('name')).'" title="'.rex_escape($title).'">'.rex_escape($value).'</a>';
            }
        }

        return $return;
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel()]);
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();
        $value = $params['value'];
        $field = $params['field']->getName();

        if ($value == '(empty)') {
            return ' (' . $sql->escapeIdentifier($field) . ' = "" or ' . $sql->escapeIdentifier($field) . ' IS NULL) ';
        }
        if ($value == '!(empty)') {
            return ' (' . $sql->escapeIdentifier($field) . ' <> "" and ' . $sql->escapeIdentifier($field) . ' IS NOT NULL) ';
        }

        $pos = strpos($value, '*');
        if ($pos !== false) {
            $value = str_replace('%', '\%', $value);
            $value = str_replace('*', '%', $value);
            return $sql->escapeIdentifier($field) . ' LIKE ' . $sql->escape($value);
        }
        return $sql->escapeIdentifier($field) . ' = ' . $sql->escape($value);
    }
}
