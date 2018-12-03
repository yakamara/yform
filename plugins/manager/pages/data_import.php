<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @var rex_yform_manager $this
 */

ini_set('auto_detect_line_endings', true);

$show_importform = true;
$show_list = false;

$fields = [];
foreach($this->table->getFields() as $field) {
    $fields[$field->getName()] = $field;
}

$divider = rex_request('divider', 'string', ';');
$missing_columns = rex_request('missing_columns', 'int');
$debug = rex_request('debug', 'string');

if (!in_array($divider, [';', ',', 'tab'])) {
    $divider = ',';
}

// 1 = ignore missing fields
// 2 = addfield if missing
// 3 = error if fields are missing
if ($missing_columns != 2 && $missing_columns != 3) {
    $missing_columns = 1;
}
if ($debug != 1) {
    $debug = 0;
}

if (rex_request('send', 'int', 0) == 1) {
    // Daten wurden übertragen
    if (!isset($_FILES['file_new']) || $_FILES['file_new']['tmp_name'] == '') {
        echo rex_view::error(rex_i18n::msg('yform_manager_import_error_missingfile'));
    } else {
        $func = '';
        $show_importform = false;

        $fieldarray = [];
        $filename = $_FILES['file_new']['tmp_name'];

        $div = $divider;
        if ($div == 'tab') {
            $div = "\t";
        }

        $counter = 0;  // importierte
        $dcounter = 0; // nicht imporierte
        $ecounter = 0; // leere reihen
        $rcounter = 0; // replace counter
        $icounter = 0; // insert counter
        $errorcounter = 0;

        $import_start = true;
        $import_start = rex_extension::registerPoint(new rex_extension_point(
                'YFORM_DATASET_IMPORT',
                $import_start,
                [
                    'divider' => $div,
                    'table' => $this->table,
                    'filename' => $filename,
                    'missing_columns' => $missing_columns,
                    'debug' => $debug,
                ]
            ));

        if ($import_start) {
            $fp = fopen($filename, 'r');
            $firstbytes = fread($fp, 3);
            $bom = pack('CCC', 0xef, 0xbb, 0xbf);
            if ($bom != $firstbytes) {
                rewind($fp);
            }

            $idColumn = null;
            while (($line_array = fgetcsv($fp, 30384, $div)) !== false) {
                if (count($fieldarray) == 0) {

                    $fieldarray = $line_array;
                    $fieldarray = array_map('rex_string::normalize', $fieldarray);

                    if (in_array('', $fieldarray)) {
                        echo rex_view::error(rex_i18n::msg('yform_manager_import_error_missingfielddefinition'));
                        $show_importform = true;
                        $func = 'import';
                        break;
                    }

                    if (count($fieldarray) != count(array_unique($fieldarray))) {
                        echo rex_view::error(rex_i18n::msg('yform_manager_import_error_duplicatefielddefinition'));
                        $show_importform = true;
                        $func = 'import';
                        break;
                    }

                    $mc = [];
                    foreach ($fieldarray as $k => $v) {
                        $v = rex_string::normalize($v);
                        $fieldarray[$k] = $v;
                        if (!array_key_exists($fieldarray[$k], $fields) && $fieldarray[$k] != 'id') {
                            $mc[$fieldarray[$k]] = $fieldarray[$k];
                        }
                        if ('id' === $fieldarray[$k]) {
                            $idColumn = $k;
                        }
                    }

                    if (count($mc) > 0) {
                        if ($missing_columns == 3) {
                            echo rex_view::error(rex_i18n::msg('yform_manager_import_error_missingfields', implode(', ', $mc)));
                            $show_importform = true;
                            $func = 'import';
                            break;
                        }
                        if ($missing_columns == 2) {
                            $error = false;
                            $i = rex_sql::factory();
                            foreach ($mc as $mcc) {

                                rex_sql::factory()
                                    ->setTable(rex_yform_manager_field::table())
                                    ->setValue('table_name', $this->table->getTablename())
                                    ->setValue('prio', 999)
                                    ->setValue('type_id', 'value')
                                    ->setValue('type_name', 'text')
                                    ->setValue('name', $mcc)
                                    ->setValue('label', 'TEXT `'.$mcc.'`')
                                    ->setValue('list_hidden', 0)
                                    ->setValue('db_type', 'text')
                                    ->insert();

                                echo rex_view::info(rex_i18n::msg('yform_manager_import_field_added', $mcc));
                            }

                            rex_yform_manager_table_api::generateTablesAndFields();

                            if ($error) {
                                echo rex_view::error(rex_i18n::msg('yform_manager_import_error_import_stopped'));
                                $show_importform = true;
                                break;
                            }

                            $fields = [];
                            foreach(rex_yform_manager_table::get($this->table->getTableName()) as $field) {
                                $fields[$field->getName()] = $field;
                            }

                        } else {
                            if (count($fieldarray) == count($mc)) {
                                echo rex_view::error(rex_i18n::msg('yform_manager_import_error_min_missingfields', implode(', ', $mc)));
                                $show_importform = true;
                                break;
                            }

                            foreach ($fieldarray as $k => $name) {
                                if (isset($mc[$name])) {
                                    unset($fieldarray[$k]);
                                }
                            }
                        }
                    }

                } else {
                    if (!$line_array) {
                        break;
                    }

                    if (null !== $idColumn && isset($line_array[$idColumn])) {
                        $id = $line_array[$idColumn];
                        $dataset = $this->table->getRawDataset($id);
                    } else {
                        $id = null;
                        $dataset = $this->table->createDataset();
                    }

                    $exists = $dataset->exists();

                    foreach ($line_array as $k => $v) {
                        if (empty($fieldarray[$k]) || 'id' === $fieldarray[$k]) {
                            continue;
                        }

                        $dataset->setValue($fieldarray[$k], $v);
                    }

                    ++$counter;

                    $dataset->save();

                    if ($messages = $dataset->getMessages()) {
                        $messages = array_unique($messages);
                        foreach ($messages as $key => $msg) {
                            if ($msg == '') {
                                $msg = rex_i18n::msg('yform_manager_import_error_messagemissing');
                            } else {
                                $msg = rex_i18n::translate($msg);
                            }
                        }

                        ++$dcounter;
                        $dataId = 'ID: '.$id;
                        echo rex_view::error(rex_i18n::msg('yform_manager_import_error_dataimport', $dataId, '<br />* ' .implode('<br />* ', $messages)));
                    } else if ($exists) {
                        ++$rcounter;
                    } else {
                        ++$icounter;
                    }
                }

                $show_list = true;
            }

            rex_extension::registerPoint(new rex_extension_point(
                'YFORM_DATASET_IMPORTED',
                '',
                [
                    'divider' => $div,
                    'table' => $this->table,
                    'filename' => $filename,
                    'missing_columns' => $missing_columns,
                    'debug' => $debug,
                    'data_imported' => $counter,  // importierte
                    'data_not_imported' => $dcounter, // nicht imporierte
                    'data_empty_rows' => $ecounter, // leere reihen
                    'data_replaced' => $rcounter, // replace counter
                    'data_inserted' => $icounter, // insert counter
                    'data_errors' => $errorcounter,
                ]
            ));

            echo rex_view::info(rex_i18n::msg('yform_manager_import_error_import', ($icounter + $rcounter), $icounter, $rcounter));
        } else {
            echo rex_view::info(rex_i18n::msg('yform_manager_import_error_not_started'));
        }

        if ($dcounter > 0) {
            echo rex_view::error(rex_i18n::msg('yform_manager_import_info_data_imported', $dcounter));
        }

        rex_yform_manager_table::deleteCache();
    }
}

if ($show_importform) {
    $hidden = '
        <input type="hidden" name="func" value="import" />
        <input type="hidden" name="send" value="1" />';

    foreach ($this->getLinkVars() as $k => $v) {
        $hidden .= '<input type="hidden" name="' . $k . '" value="' . addslashes($v) . '" />';
    }

    $content = '
        <p>' . rex_i18n::msg('yform_manager_import_csv_info') . '</p>
        <fieldset>
            ' . $hidden . '
    ';

    $formElements = [];
    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_if_no_column_ignore') . '</label>';
    $n['field'] = '<input type="radio" name="missing_columns" value="1"' . (($missing_columns == '1') ? 'checked' : '') . ' />';
    $formElements[] = $n;

    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_if_no_column_addtext') . '</label>';
    $n['field'] = '<input type="radio" name="missing_columns" value="2"' . (($missing_columns == '2') ? 'checked' : '') . ' />';
    $formElements[] = $n;

    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_if_no_column_break') . '</label>';
    $n['field'] = '<input type="radio" name="missing_columns" value="3"' . (($missing_columns == '3') ? 'checked' : '') . ' />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $radios = $fragment->parse('core/form/radio.php');

    $formElements = [];
    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_if_no_column') . '</label>';
    $n['field'] = $radios;
    $formElements[] = $n;

    $a = new rex_select();
    $a->setName('divider');
    $a->setId('divider');
    $a->addOption(rex_i18n::msg('yform_manager_import_divider_semicolon') . ' (;)', ';');
    $a->addOption(rex_i18n::msg('yform_manager_import_divider_comma') . ' (,)', ',');
    $a->addOption(rex_i18n::msg('yform_manager_import_divider_tab') . '', 'tab');
    $a->setSelected($divider);

    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_divider') . '</label>';
    $n['field'] = '<div class="yform-select-style">' . $a->get() . '</div>';
    $formElements[] = $n;

    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_file') . '</label>';
    $n['field'] = '<input class="form-control" type="file" name="file_new" />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    $content .= '</fieldset>';

    $formElements = [];

    $n = [];
    $n['field'] = '<a class="btn btn-abort" href="' . rex_url::currentBackendPage($this->getLinkVars()) . '">' . rex_i18n::msg('form_abort') . '</a>';
    $formElements[] = $n;

    $n = [];
    $n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . rex_i18n::msg('yform_manager_import_start') . '">' . rex_i18n::msg('yform_manager_import_start') . '</button>';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $buttons = $fragment->parse('core/form/submit.php');

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', rex_i18n::msg('yform_manager_import_csv'), false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    $content = $fragment->parse('core/page/section.php');

    $content = '
    <form action="' . rex_url::currentBackendPage() . '" data-pjax="false" method="post" enctype="multipart/form-data">
        ' . $content . '
    </form>';

    echo $content;
}
