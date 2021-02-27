<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_upload extends rex_yform_value_abstract
{
    public $_upload_sessionKey = '';

    public function enterObject()
    {
        // TODO: Alte Dateien mit selben key löschen ?
        // TODO: Original / Initial File noch speichern und Im Formular vielleict zurückholen können ?
        // TODO: Ytemplate anpassen und nicht unique kex nehmen, sondern direkt den Feldnamen
        // erstmal so.
        // billiger hack wegen yorm, damit bei yorm save(), der wert nicht gelöscht wird
        //  TODO?: Solange beim Abschicken die session mit dem value nicht gelöscht wird. ist der vorhanden.

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
        $error_messages['destination_error'] = isset($error_messages[5]) ? rex_i18n::translate($error_messages[5]) : 'destination_error ';

        $errors = [];

        rex_login::startSession();

        $delete = (bool) @$this->params['this']->getFieldValue($this->getName(), [$this->getId(), 'delete']);
        if ($delete) {
            $this->unsetSessionVar('value');
            $this->unsetSessionVar('file');
        }

        if (!$this->params['send']) {
            $this->setSessionVar('value', (string) $this->getValue());
            $this->setSessionVar('original_value', (string) $this->getValue());
        }

        $FILE = null;
        if (isset($_FILES[$this->getSessionKey()]) && '' != $_FILES[$this->getSessionKey()]['name']) {
            $FILE['size'] = $_FILES[$this->getSessionKey()]['size'];
            $FILE['name'] = mb_strtolower(preg_replace('/[^a-zA-Z0-9.\-\$\+]/', '_', $_FILES[$this->getSessionKey()]['name']));
            $FILE['type'] = $_FILES[$this->getSessionKey()]['type'];
            $FILE['error'] = $_FILES[$this->getSessionKey()]['error'];
            $FILE['tmp_name'] = $_FILES[$this->getSessionKey()]['tmp_name'];
            $FILE['tmp_yform_name'] = $temp_folder . '/' . $this->getSessionKey() . '_' . $this->getId() . '_' . $FILE['name'];
            $FILE['upload_folder'] = $upload_folder;
            $FILE['upload_name'] = $this->getSessionKey().'_'.$FILE['name']; // default_name

            unset($_FILES[$this->getSessionKey()]);

            $extensions_array = explode(',', $this->getElement('types'));
            $ext = '.' . pathinfo($FILE['name'], PATHINFO_EXTENSION);

            if (
                ('*' != $this->getElement('types')) &&
                (!in_array(mb_strtolower($ext), $extensions_array) && !in_array(mb_strtoupper($ext), $extensions_array))
            ) {
                $errors[] = $error_messages['type_error'];
                unset($FILE);
            }

            if (isset($FILE)) {
                $sizes = array_map('intval', explode(',', $this->getElement('sizes')));
                $min_size = count($sizes) > 1 ? (int) ($sizes[0] * 1024) : 0;
                $max_size = count($sizes) > 1 ? (int) ($sizes[1] * 1024) : (int) ($sizes[0] * 1024);

                if ('' != $this->getElement('sizes') && $FILE['size'] > $max_size) {
                    $errors[] = $error_messages['max_error'];
                    unset($FILE);
                } elseif ('' != $this->getElement('sizes') && $FILE['size'] < $min_size) {
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
                $this->setSessionVar('file', $FILE);
            } else {
                $this->unsetSessionVar('file');
            }
        }

        $filename = '';
        $filepath = '';
        $real_filepath = '';

        // Datei war bereits vorhanden - vorbereitung für den Download und setzen des Values
        if ('' != $this->getSessionVar('value', 'string', '')) {
            $filename = (string) $this->getSessionVar('value', 'string', '');
            $filepath = (string) $this->upload_getFolder() . '/' . $this->getParam('main_id') . '_' . $filename;
            if (file_exists($filepath)) {
                $real_filepath = $filepath;
            } else {
                $this->unsetSessionVar('value');
                $filename = '';
                $filepath = '';
            }
        }

        // Datei aus Upload vorhanden - aber noch nicht gespeichert - vorbereitung für den Download und setzen des Values
        if ($this->getSessionVar('file', 'array', null)) {
            $FILE = $this->getSessionVar('file', 'array');
            if ('' == $FILE['tmp_yform_name'] || !file_exists($FILE['tmp_yform_name'])) {
                $this->unsetSessionVar('file');
            } else {
                $filepath = $FILE['tmp_yform_name'];
                $filename = $FILE['name'];
                $real_filepath = $FILE['upload_folder'].'/'.$FILE['upload_name'];
            }
        }

        // Download starten - wenn Dateinamen übereinstimmen
        if (rex::isBackend() &&
            (
                rex_request('rex_upload_downloadfile', 'string') == $this->getName()) &&
                '' != $filename &&
                '' != $filepath
            ) {
            $this->upload_checkdownloadFile($filename, $filepath);
        }

        if (!$delete &&
                $this->params['send'] &&
                '' != $this->getValue() &&
                is_string($this->getValue()) &&
                !$this->getSessionVar('file', 'array', null)
                ) {
            $filename = $this->getValue();
        }

        $this->setValue($filename);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['email'][$this->getName().'_folder'] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if ('' != $filepath) {
            $this->params['value_pool']['files'][$this->getName()] = [$filename, $filepath, $real_filepath];
        }

        if ($this->params['send'] && 1 == $this->getElement('required') && '' == $filename) {
            $errors[] = $error_messages['empty_error'];
        }

        if ($this->params['send'] && count($errors) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $errors);
        }

        $download_link = '';
        if (rex::isBackend()) {
            $download_link = self::upload_getDownloadLink($this->params['main_table'], $this->getName(), $this->params['main_id']);
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.upload.tpl.php', [
                'unique' => $this->getSessionKey(),
                'filename' => $filename,
                'error_messages' => $error_messages,
                'download_link' => $download_link,
            ]);
        }

        return $this;
    }

    public function postAction()
    {
        if ($this->getSessionVar('file', 'array', null)) {
            $FILE = $this->getSessionVar('file', 'array', null);

            if (file_exists($FILE['tmp_yform_name'])) {
                $main_id = $this->getParam('main_id');
                if ('' != $main_id && -1 != $main_id) {
                    $FILE['upload_name'] = $main_id.'_'.$FILE['name'];
                } else {
                    $FILE['upload_name'] = $this->getSessionKey().'_'.$FILE['name'];
                }

                $upload_filefolder = $FILE['upload_folder'].'/'.$FILE['upload_name'];

                if (!move_uploaded_file($FILE['tmp_yform_name'], $upload_filefolder)) {
                    if (!copy($FILE['tmp_yform_name'], $upload_filefolder)) {
                        echo 'Uploadproblem: Code-YForm-Upload-Target';
                    } else {
                        chmod($upload_filefolder, rex::getFilePerm());
                        $this->setSessionVar('value', $FILE['name']);
                    }
                } else {
                    chmod($upload_filefolder, rex::getFilePerm());
                    $this->setSessionVar('value', $FILE['name']);
                }
            }
        }

        $this->unsetSessionVar('file');
        $this->unsetSessionVar('value');
        $this->unsetSessionVar('original_value');

        // delete temp files from this formfield
        $temp_folder = rex_path::pluginData('yform', 'manager', 'upload/temp');
        foreach (glob($temp_folder .'/'.$this->getSessionKey().'*') as $f) {
            unlink($f);
        }

        // delete old files from cache
        $cu = date('U');
        $offset = (60 * 60 * 0.5); // 30 min
        $dir = $temp_folder.'/';
        if ($dh = opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                $f = $dir.$file;
                $fu = date('U', filectime($f));
                if (($cu - $fu) > $offset && '.' != $file && '..' != $file) {
                    unlink($f);
                }
            }
        }

        parent::postAction();
    }

    public function upload_getFolder()
    {
        $folders = [];

        if ('' != $this->getParam('main_table')) {
            $folders[] = $this->getParam('main_table');
            if ('' != $this->getName()) {
                $folders[] = $this->getName();
            }
        }

        if (0 == count($folders)) {
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

    public static function upload_getDownloadLink($table_name, $field_name, $data_id)
    {
        if ('' != $table_name && '' != $field_name && 0 < $data_id) {
            return '/redaxo/index.php?page=yform/manager/data_edit&table_name='.$table_name.'&data_id='.$data_id.'&func=edit&rex_upload_downloadfile='.urlencode($field_name);
        }
        return '';
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
            if ('' != $value) {
                $return = '<a href="' . self::upload_getDownloadLink($field->getElement('table_name'), $field->getElement('name'), $params['list']->getValue('id')) . '" title="'.rex_escape($title).'">'.rex_escape($value).'</a>';
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

        if ('(empty)' == $value) {
            return ' (' . $sql->escapeIdentifier($field) . ' = "" or ' . $sql->escapeIdentifier($field) . ' IS NULL) ';
        }
        if ('!(empty)' == $value) {
            return ' (' . $sql->escapeIdentifier($field) . ' <> "" and ' . $sql->escapeIdentifier($field) . ' IS NOT NULL) ';
        }

        $pos = strpos($value, '*');
        if (false !== $pos) {
            $value = str_replace('%', '\%', $value);
            $value = str_replace('*', '%', $value);
            return $sql->escapeIdentifier($field) . ' LIKE ' . $sql->escape($value);
        }
        return $sql->escapeIdentifier($field) . ' = ' . $sql->escape($value);
    }

    public function getSessionKey()
    {
        // return rex_string::normalize(session_id().'-'.$this->params['form_name'].'-'.$this->getName());

        if ('' != $this->_upload_sessionKey) {
            return $this->_upload_sessionKey;
        }

        // key wurde aus dem Formular übertragen ?
        $this->_upload_sessionKey = $this->params['this']->getFieldValue($this->getName(), [$this->getId(), 'unique']);
        if ('' == $this->_upload_sessionKey) {
            $this->_upload_sessionKey = bin2hex(openssl_random_pseudo_bytes(32, $cstrong));
        }

        return $this->_upload_sessionKey;
    }

    public function setSessionVar($key, $value)
    {
        $sessionVars = rex_session($this->getSessionKey(), 'array', []);
        $sessionVars[$key] = $value;
        rex_set_session($this->getSessionKey(), $sessionVars);
    }

    public function getSessionVar($key, $varType = 'string', $default = '')
    {
        $sessionVars = rex_session($this->getSessionKey(), 'array', []);

        if (!is_scalar($varType)) {
            throw new InvalidArgumentException('Scalar expected for $needle in arrayKeyCast(), got '. gettype($varType) .'!');
        }

        if (array_key_exists($key, $sessionVars)) {
            return rex_type::cast($sessionVars[$key], $varType);
        }

        if ('' === $default) {
            return rex_type::cast($default, $varType);
        }

        return $default;
    }

    public function unsetSessionVar($key)
    {
        $sessionVars = rex_session($this->getSessionKey(), 'array', []);
        unset($sessionVars[$key]);
        rex_set_session($this->getSessionKey(), $sessionVars);
    }

    public function unsetSession()
    {
        rex_set_session($this->getSessionKey(), []);
    }
}
