<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_upload extends rex_yform_value_abstract
{
    public string $_upload_sessionKey = '';

    public function enterObject()
    {
        // TODO: Alte Dateien mit selben key löschen ?
        // TODO: Original / Initial File noch speichern und Im Formular vielleict zurückholen können ?
        // TODO: Ytemplate anpassen und nicht unique kex nehmen, sondern direkt den Feldnamen
        // erstmal so.
        // billiger hack wegen yorm, damit bei yorm save(), der wert nicht gelöscht wird
        // TODO?: Solange beim Abschicken die session mit dem value nicht gelöscht wird. ist der vorhanden.

        $upload_folder = self::upload_getFolder();
        $temp_folder = rex_path::pluginData('yform', 'manager', 'upload/temp');

        rex_dir::create($upload_folder);
        rex_dir::create($temp_folder);

        $configuration = json_decode($this->getElement('config'), true);

        if (!isset($configuration['sizes'])) {
            $configuration['sizes'] = [];
            $sizes = array_map('intval', explode(',', $this->getElement('sizes')));
            $configuration['sizes']['min'] = count($sizes) > 1 ? (int) ($sizes[0] * 1024) : 0;
            $configuration['sizes']['max'] = count($sizes) > 1 ? (int) ($sizes[1] * 1024) : (int) ($sizes[0] * 1024);
        }

        if (!isset($configuration['sizes']['min'])) {
            $configuration['sizes']['min'] = 0;
        }
        if (!isset($configuration['sizes']['max'])) {
            $configuration['sizes']['max'] = 500000;
        }
        if (!isset($configuration['allowed_extensions'])) {
            $configuration['allowed_extensions'] = explode(',', str_replace('.', '', $this->getElement('types') ?: '*'));
        }

        if (!isset($configuration['disallowed_extensions'])) {
            $configuration['disallowed_extensions'] = ['exe'];
        }

        if (!isset($configuration['check'])) {
            $configuration['check'] = []; // "multiple_extensions","zip_archive"
        }

        if (!isset($configuration['messages'])) {
            $configuration['messages'] = [];
        }

        if (!isset($configuration['callback'])) {
            $configuration['callback'] = [];
        }

        // deprecated
        $messages = [];
        if (!is_array($this->getElement('messages'))) {
            $messages = explode(',', $this->getElement('messages'));
        }

        if (!isset($configuration['messages']['min_error'])) {
            $configuration['messages']['min_error'] = isset($messages[0]) ? rex_i18n::translate($messages[0]) : 'min-error-msg';
        }

        if (!isset($configuration['messages']['max_error'])) {
            $configuration['messages']['max_error'] = isset($messages[1]) ? rex_i18n::translate($messages[1]) : 'max-error-msg';
        }

        if (!isset($configuration['messages']['type_error'])) {
            $configuration['messages']['type_error'] = isset($messages[2]) ? rex_i18n::translate($messages[2]) : 'type-error-msg';
        }

        if (!isset($configuration['messages']['empty_error'])) {
            $configuration['messages']['empty_error'] = isset($messages[3]) ? rex_i18n::translate($messages[3]) : 'empty-error-msg';
        }

        if (!isset($configuration['messages']['system_error'])) {
            $configuration['messages']['system_error'] = isset($messages[5]) ? rex_i18n::translate($messages[5]) : 'system_error-msg';
        }

        if (!isset($configuration['messages']['type_multiple_error'])) {
            $configuration['messages']['type_multiple_error'] = isset($messages[6]) ? rex_i18n::translate($messages[6]) : 'type_multiple-msg';
        }

        if (!isset($configuration['messages']['zip-type_error'])) {
            $configuration['messages']['zip-type_error'] = isset($messages[7]) ? rex_i18n::translate($messages[7]) : 'zip-type_error-msg';
        }

        if (!isset($configuration['messages']['type_zip_error'])) {
            $configuration['messages']['type_zip_error'] = isset($messages[8]) ? rex_i18n::translate($messages[8]) : 'type_zip_error-msg';
        }

        if (!isset($configuration['messages']['extension_zip_type_error'])) {
            $configuration['messages']['extension_zip_type_error'] = isset($messages[9]) ? rex_i18n::translate($messages[9]) : 'extension_zip_type_error-msg {0}';
        }

        if (!isset($configuration['messages']['delete_file'])) {
            $configuration['messages']['delete_file'] = isset($messages[4]) ? rex_i18n::translate($messages[4]) : 'delete-msg';
        }

        $errors = [];

        rex_login::startSession();

        $delete = (bool) @$this->params['this']->getFieldValue($this->getName(), [$this->getId(), 'delete']);
        if ($delete) {
            $this->upload_unsetSessionVar('value');
            $this->upload_unsetSessionVar('file');
        }

        if (!$this->params['send']) {
            $this->upload_setSessionVar('value', (string) $this->getValue());
            $this->upload_setSessionVar('original_value', (string) $this->getValue());
        }

        if (!$this->isEditable()) {
            unset($_FILES[$this->upload_getSessionKey()]);
        }

        $FILE = null;
        if (isset($_FILES[$this->upload_getSessionKey()]) && '' != $_FILES[$this->upload_getSessionKey()]['name']) {
            $FILE['size'] = $_FILES[$this->upload_getSessionKey()]['size'];
            $FILE['name'] = mb_strtolower(preg_replace('/[^a-zA-Z0-9.\-\$\+]/', '_', $_FILES[$this->upload_getSessionKey()]['name']));
            $FILE['type'] = $_FILES[$this->upload_getSessionKey()]['type'];
            $FILE['error'] = $_FILES[$this->upload_getSessionKey()]['error'];
            $FILE['tmp_name'] = $_FILES[$this->upload_getSessionKey()]['tmp_name'];
            $FILE['tmp_yform_name'] = $temp_folder . '/' . $this->upload_getSessionKey() . '_' . $this->getId() . '_' . $FILE['name'];
            $FILE['upload_folder'] = $upload_folder;
            $FILE['upload_name'] = $this->upload_getSessionKey() . '_' . $FILE['name']; // default_name

            unset($_FILES[$this->upload_getSessionKey()]);

            if (UPLOAD_ERR_OK !== $FILE['error']) {
                // copied from https://www.php.net/manual/de/features.file-upload.errors.php
                switch ($FILE['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $system_message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $system_message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $system_message = 'The uploaded file was only partially uploaded';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $system_message = 'No file was uploaded';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $system_message = 'Missing a temporary folder';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $system_message = 'Failed to write file to disk';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $system_message = 'File upload stopped by extension';
                        break;
                    default:
                        $system_message = 'Unknown upload error';
                        break;
                }
                if ($this->params['debug']) {
                    dump($system_message);
                }
                $errors[] = $configuration['messages']['system_error'];
                unset($FILE);
            }

            if (isset($FILE['name'])) {
                $error_extensions = self::upload_checkExtensions($FILE, $configuration);
                if (0 < count($error_extensions)) {
                    $errors += $error_extensions;
                    unset($FILE);
                }
            }

            if (isset($FILE['name'])) {
                $errors_callbacks = [];
                foreach ($configuration['callback'] as $callback) {
                    $errors_callback = call_user_func($callback, ['file' => $FILE, 'configuration' => $configuration]);
                    if (0 < count($errors_callback)) {
                        $errors_callbacks += $errors_callback;
                    }
                }
                if (0 < count($errors_callbacks)) {
                    $errors += $errors_callbacks;
                    unset($FILE);
                }
            }

            if (isset($FILE)) {
                if ('' != $this->getElement('sizes') && $FILE['size'] > $configuration['sizes']['max']) {
                    $errors[] = $configuration['messages']['max_error'];
                    unset($FILE);
                } elseif ('' != $this->getElement('sizes') && $FILE['size'] < $configuration['sizes']['min']) {
                    $errors[] = $configuration['messages']['min_error'];
                    unset($FILE);
                }
            }

            if (isset($FILE)) {
                if (!@move_uploaded_file($FILE['tmp_name'], $FILE['tmp_yform_name'])) {
                    if (!@copy($FILE['tmp_name'], $FILE['tmp_yform_name'])) {
                        if ($this->params['debug']) {
                            dump('uploade file move/copy failed: destination folder problem?');
                        }
                        $errors[] = $configuration['messages']['system_error'];
                        unset($FILE);
                    } else {
                        @chmod($FILE['tmp_yform_name'], rex::getFilePerm());
                    }
                }
            }

            if (isset($FILE)) {
                $this->upload_setSessionVar('file', $FILE);
            } else {
                $this->upload_unsetSessionVar('file');
            }
        }

        $filename = '';
        $filepath = '';
        $real_filepath = '';

        // Datei war bereits vorhanden - vorbereitung für den Download und setzen des Values
        if ('' != $this->upload_getSessionVar('value')) {
            $filename = (string) $this->upload_getSessionVar('value');
            $filepath = $this->upload_getFolder() . '/' . $this->getParam('main_id') . '_' . $filename;
            if (file_exists($filepath)) {
                $real_filepath = $filepath;
            } else {
                $this->upload_unsetSessionVar('value');
                $filename = '';
                $filepath = '';
            }
        }

        // Datei aus Upload vorhanden - aber noch nicht gespeichert - vorbereitung für den Download und setzen des Values
        if ($this->upload_getSessionVar('file', 'array', null)) {
            $FILE = $this->upload_getSessionVar('file', 'array');
            if ('' == $FILE['tmp_yform_name'] || !file_exists($FILE['tmp_yform_name'])) {
                $this->upload_unsetSessionVar('file');
            } else {
                $filepath = $FILE['tmp_yform_name'];
                $filename = $FILE['name'];
                $real_filepath = $FILE['upload_folder'] . '/' . $FILE['upload_name'];
            }
        }

        // Download starten - wenn Dateinamen übereinstimmen
        if (rex::isBackend() &&
            (
                rex_request('rex_upload_downloadfile', 'string') == $this->getName()
            ) &&
                '' != $filename &&
                '' != $filepath
        ) {
            $this->upload_checkdownloadFile($filename, $filepath);
        }

        if (!$delete &&
                $this->params['send'] &&
                '' != $this->getValue() &&
                is_string($this->getValue()) &&
                !$this->upload_getSessionVar('file', 'array', null)
        ) {
            $filename = $this->getValue();
        }

        $this->setValue($filename);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['email'][$this->getName() . '_folder'] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if ('' != $filepath) {
            $this->params['value_pool']['files'][$this->getName()] = [$filename, $filepath, $real_filepath];
        }

        if ($this->params['send'] && 1 == $this->getElement('required') && '' == $filename) {
            $errors[] = $configuration['messages']['empty_error'];
        }

        if ($this->params['send'] && count($errors) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $errors);
        }

        $download_link = '';
        if (rex::isBackend()) {
            $download_link = self::upload_getDownloadLink($this->params['main_table'], $this->getName(), (int) $this->params['main_id']);
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.upload-view.tpl.php', 'value.view.tpl.php'], [
                    'unique' => $this->upload_getSessionKey(),
                    'filename' => $filename,
                    'error_messages' => $configuration['messages'],
                    'download_link' => $download_link,
                    'configuration' => $configuration,
                ]);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.upload.tpl.php', [
                    'unique' => $this->upload_getSessionKey(),
                    'filename' => $filename,
                    'error_messages' => $configuration['messages'],
                    'download_link' => $download_link,
                    'configuration' => $configuration,
                ]);
            }
        }

        return $this;
    }

    public function postAction(): void
    {
        if ($this->upload_getSessionVar('file', 'array', null)) {
            $FILE = $this->upload_getSessionVar('file', 'array', null);

            if (file_exists($FILE['tmp_yform_name'])) {
                $main_id = $this->getParam('main_id');
                if ('' != $main_id && -1 != $main_id) {
                    $FILE['upload_name'] = $main_id . '_' . $FILE['name'];
                } else {
                    $FILE['upload_name'] = $this->upload_getSessionKey() . '_' . $FILE['name'];
                }

                $upload_filefolder = $FILE['upload_folder'] . '/' . $FILE['upload_name'];

                if (!move_uploaded_file($FILE['tmp_yform_name'], $upload_filefolder)) {
                    if (!copy($FILE['tmp_yform_name'], $upload_filefolder)) {
                        echo 'Uploadproblem: Code-YForm-Upload-Target';
                    } else {
                        chmod($upload_filefolder, rex::getFilePerm());
                        $this->upload_setSessionVar('value', $FILE['name']);
                    }
                } else {
                    chmod($upload_filefolder, rex::getFilePerm());
                    $this->upload_setSessionVar('value', $FILE['name']);
                }
            }
        }

        if (1 != $this->params['send']) {
            $this->upload_unsetSessionVar('file');
            $this->upload_unsetSessionVar('value');
            $this->upload_unsetSessionVar('original_value');
        }

        // delete temp files from this formfield
        $temp_folder = rex_path::pluginData('yform', 'manager', 'upload/temp');
        foreach (glob($temp_folder . '/' . $this->upload_getSessionKey() . '*') as $f) {
            unlink($f);
        }

        // delete old files from cache
        $cu = date('U');
        $offset = (60 * 60 * 0.5); // 30 min
        $dir = $temp_folder . '/';
        if ($dh = opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                $f = $dir . $file;
                $fu = date('U', filectime($f));
                if (($cu - $fu) > $offset && '.' != $file && '..' != $file) {
                    unlink($f);
                }
            }
        }

        parent::postAction();
    }

    public function upload_getFolder(): string
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

        return rex_path::pluginData('yform', 'manager', 'upload/' . implode('/', $folders));
    }

    public static function upload_checkdownloadFile(string $filename, string $filepath): void
    {
        if (file_exists($filepath)) {
            ob_end_clean();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
    }

    public function getDescription(): string
    {
        return 'upload|name|label|Maximale Größe in Kb oder Range 100,500 oder leer lassen| endungenmitpunktmitkommasepariert oder *| pflicht=1 | min_error_msg,max_error_msg,type_error_msg,empty_error_msg,delete_file_msg,system_error_msg';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'upload',
            'values' => [
                'name' => ['type' => 'name',      'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'sizes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_sizes')],
                'types' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_types'),    'notice' => rex_i18n::msg('yform_values_upload_types_notice'), 'default' => ''],
                'required' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_upload_required')],
                'messages' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_messages'), 'notice' => rex_i18n::msg('yform_values_upload_messages_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'config' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_config'), 'notice' => rex_i18n::msg('yform_values_upload_config_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_upload_description'),
            'db_type' => ['text'],
            'multi_edit' => true,
        ];
    }

    public static function upload_getDownloadLink(string $table_name, string $field_name, int $data_id): string
    {
        if ('' != $table_name && '' != $field_name && 0 < $data_id) {
            return '/redaxo/index.php?page=yform/manager/data_edit&table_name=' . $table_name . '&data_id=' . $data_id . '&func=edit&rex_upload_downloadfile=' . urlencode($field_name);
        }
        return '';
    }

    public static function upload_checkExtensions(array $FILE, array $configuration): array
    {
        $Filename = $FILE['name'];

        $errors = [];
        $ext = mb_strtolower(pathinfo($Filename, PATHINFO_EXTENSION));
        $configuration['allowed_extensions'] = array_map(static function ($a) {
            return mb_strtolower($a);
        }, $configuration['allowed_extensions']);

        if (
            (!in_array('*', $configuration['allowed_extensions'])) &&
            (!in_array($ext, $configuration['allowed_extensions']))
        ) {
            $errors[] = $configuration['messages']['type_error'] ?? 'extension-type-error';
        }

        if (
            isset($configuration['check']) &&
            in_array('multiple_extensions', $configuration['check']) &&
            0 < count(array_intersect(explode('.', $Filename), $configuration['disallowed_extensions']))
        ) {
            $errors[] = $configuration['messages']['type_multiple_error'] ?? 'multiple-extension-type-error: ' . implode(', ', array_intersect(explode('.', $Filename), $configuration['disallowed_extensions']));
        }

        if (
            isset($configuration['check']) &&
            in_array('zip_archive', $configuration['check'])
        ) {
            $zip = new ZipArchive();
            if ($zip->open($FILE['tmp_name'])) {
                $zip = new ZipArchive();
                if ('zip' == $ext) {
                    if ($zip->open($FILE['tmp_name'])) {
                        $zip_error_files = [];

                        for ($i = 0; $i < $zip->numFiles; ++$i) {
                            $iZipFileName = $zip->getNameIndex($i);
                            $i_ext = mb_strtolower(pathinfo($iZipFileName, PATHINFO_EXTENSION));

                            if (
                                (!in_array('*', $configuration['allowed_extensions'])) &&
                                (!in_array($i_ext, $configuration['allowed_extensions']))
                            ) {
                                if (1 < count($errors)) {
                                    $errors[] = ' ... ';
                                    break;
                                }

                                $zip_error_files[] = $iZipFileName;
                            }
                        }

                        if (3 < count($zip_error_files)) {
                            $amount_files = count($zip_error_files);
                            $zip_error_files = array_chunk($zip_error_files, 3)[0];
                            $zip_error_files[] = ' ... [' . ($amount_files - count($zip_error_files)) . '] ';
                        }

                        if (0 < count($zip_error_files)) {
                            $temp_msg = $configuration['messages']['zip-type_error'] ?? 'extension-zip-type-error: {0}';
                            $errors[] = str_replace('{0}', implode(', ', $zip_error_files), $temp_msg);
                        }
                    }
                }
            } else {
                $errors[] = $configuration['messages']['type_zip_error'] ?? 'zip-type-error';
            }
        }

        return $errors;
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
        $value = $params['subject'];
        $length = mb_strlen($value);
        /** @var rex_list|null $list */
        $list = $params['list'];
        $title = $value;
        if ($length > 30) {
            $value = mb_substr($value, 0, 15) . ' ... ' . mb_substr($value, -15);
        }

        $return = $value;
        if (rex::isBackend() && $list) {
            $field = new rex_yform_manager_field($params['params']['field']);
            if ('' != $value) {
                $return = '<a href="' . self::upload_getDownloadLink($field->getElement('table_name'), $field->getElement('name'), (int) $list->getValue('id')) . '" title="' . rex_escape($title) . '">' . rex_escape($value) . '</a>';
            }
        }

        return $return;
    }

    public function upload_getSessionKey(): string
    {
        if ('' != $this->_upload_sessionKey) {
            return $this->_upload_sessionKey;
        }

        // key wurde aus dem Formular übertragen ?
        $this->_upload_sessionKey = (string) $this->params['this']->getFieldValue($this->getName(), [$this->getId(), 'unique']);
        if ('' == $this->_upload_sessionKey) {
            $this->_upload_sessionKey = bin2hex(openssl_random_pseudo_bytes(32, $cstrong));
        }

        return $this->_upload_sessionKey;
    }

    public function upload_setSessionVar(string $key, mixed $value): void
    {
        $sessionVars = rex_request::session($this->upload_getSessionKey(), 'array', []);
        $sessionVars[$key] = $value;
        rex_set_session($this->upload_getSessionKey(), $sessionVars);
    }

    public function upload_getSessionVar(string $key, string $varType = 'string', mixed $default = ''): mixed
    {
        $sessionVars = rex_session($this->upload_getSessionKey(), 'array', []);

        if (!is_scalar($varType)) {
            throw new InvalidArgumentException('Scalar expected for $needle in arrayKeyCast(), got ' . gettype($varType) . '!');
        }

        if (array_key_exists($key, $sessionVars)) {
            return rex_type::cast($sessionVars[$key], $varType);
        }

        if ('' === $default) {
            return rex_type::cast($default, $varType);
        }

        return $default;
    }

    public function upload_unsetSessionVar(string $key): void
    {
        $sessionVars = rex_session($this->upload_getSessionKey(), 'array', []);
        unset($sessionVars[$key]);
        rex_set_session($this->upload_getSessionKey(), $sessionVars);
    }

    public function upload_unsetSession(): void
    {
        rex_set_session($this->upload_getSessionKey(), []);
    }
}
