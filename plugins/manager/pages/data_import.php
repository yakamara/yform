<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

ini_set('auto_detect_line_endings', true);

$show_importform = true;
$show_list = false;

$rfields = $this->table->getColumns();

$replacefield = rex_request('replacefield', 'string');
$divider = rex_request('divider', 'string', ';');
$missing_columns = rex_request('missing_columns', 'int');
$debug = rex_request('debug', 'string');

if ($replacefield == '') {
    $replacefield = 'id';
}
if (!in_array($divider, array(';', ',', 'tab'))) {
    $divider = ',';
}
if ($missing_columns != 2 && $missing_columns != 3) {
    $missing_columns = 1;
}
if ($debug != 1) {
    $debug = 0;
}


if (rex_request('send', 'int', 0) == 1) {

    // Daten wurden übertragen
    if (!isset($_FILES['file_new']) || $_FILES['file_new']['tmp_name'] == '') {
        echo rex_view::warning(rex_i18n::msg('yform_manager_import_error_missingfile'));

    } else {

        $func = '';
        $show_importform = false;

        $fieldarray = array();
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
                'yform_DATASET_IMPORT',
                $import_start,
                [
                    'divider' => $div,
                    'table' => $this->table,
                    'filename' => $filename,
                    'replacefield' => $replacefield,
                    'missing_columns' => $missing_columns,
                    'debug' => $debug
                ]
            ));

        if ($import_start) {

            $i = rex_sql::factory();

            if ($debug) {
                $i->debugsql = 1;
            }

            $fp = fopen($filename, 'r');
            while ( ($line_array = fgetcsv($fp, 30384, $div)) !== false ) {

                if (count($fieldarray) == 0) {
                    // ******************* first line
                    $fieldarray = $line_array;

                    $mc = array();
                    foreach ($fieldarray as $k => $v) {
                        if (!array_key_exists($fieldarray[$k], $rfields) && $fieldarray[$k] != 'id') {
                            $mc[$fieldarray[$k]] = $fieldarray[$k];
                        }
                    }

                    if (count($mc) > 0) {
                        if ($missing_columns == 3) {
                            echo rex_view::warning(rex_i18n::msg('yform_manager_import_error_missingfields', implode(', ', $mc)));
                            $show_importform = true;
                            $func = 'import';
                            break;

                        } elseif ($missing_columns == 2) {
                            $error = false;
                            foreach ($mc as $mcc) {
                                $sql = 'ALTER TABLE `' . $this->table->getTablename() . '` ADD `' . mysql_real_escape_string($mcc) . '` TEXT NOT NULL;';
                                $upd = rex_sql::factory();
                                $upd->setQuery($sql);

                                if ($upd->getError()) {
                                    $error = true;
                                    echo rex_view::warning(rex_i18n::msg('yform_manager_import_error_field', $mcc, $upd->getError()));

                                } else {
                                    echo rex_view::info(rex_i18n::msg('yform_manager_import_field_added', $mcc));

                                }

                            }
                            if ($error) {
                                echo rex_view::warning(rex_i18n::msg('yform_manager_import_error_import_stopped'));
                                $show_importform = true;
                                break;
                            }

                            $rfields = $this->table->getColumns();

                        }

                    }

                } else {

                    if (!$line_array) {
                        break;

                    } else {
                        $counter++;
                        $i->setTable($this->table->getTablename());
                        $replacevalue = '';
                        foreach ($line_array as $k => $v) {
                            if ($fieldarray[$k] != '' && (array_key_exists($fieldarray[$k], $rfields) || $fieldarray[$k] == 'id')) {
                                $i->setValue($fieldarray[$k], mysql_real_escape_string($v));
                                if ($replacefield == $fieldarray[$k]) {
                                    $replacevalue = $v;

                                }
                            }
                        }

                        // noch abfrage ob $replacefield
                        $cf = rex_sql::factory();
                        $cf->setQuery('select * from ' . $this->table->getTablename() . ' where ' . $replacefield . '="' . mysql_real_escape_string($replacevalue) . '"');

                        if ($cf->getRows() > 0) {
                            $i->setWhere($replacefield . '="' . mysql_real_escape_string($replacevalue) . '"');

                            rex_extension::registerPoint(new rex_extension_point(
                                'yform_DATASET_IMPORT_DATA_UPDATE',
                                $import_start,
                                array(
                                    'divider' => $div,
                                    'table' => $this->table,
                                    'filename' => $filename,
                                    'replacefield' => $replacefield,
                                    'missing_columns' => $missing_columns,
                                    'debug' => $debug,
                                    'yform_object' => $i
                                )
                                ));

                            $i->update();
                            $error = $i->getError();
                            if ($error == '') {
                                $rcounter++;

                            } else {
                                $dcounter++;
                                echo rex_view::warning(rex_i18n::msg('yform_manager_import_error_dataimport', $error));
                            }
                        } else {

                            rex_extension::registerPoint(new rex_extension_point(
                                'yform_DATASET_IMPORT_DATA_INSERT',
                                $import_start,
                                array(
                                    'divider' => $div,
                                    'table' => $this->table,
                                    'filename' => $filename,
                                    'replacefield' => $replacefield,
                                    'missing_columns' => $missing_columns,
                                    'debug' => $debug,
                                    'yform_object' => $i
                                )
                                ));

                            $i->insert();
                            $error = $i->getError();
                            if ($error == '') {
                                $icounter++;
                            } else {
                                $dcounter++;
                                echo rex_view::warning(rex_i18n::msg('yform_manager_import_error_dataimport', $error));
                            }
                        }

                    }
                }

                $show_list = true;

            }

            rex_extension::registerPoint(new rex_extension_point(
                'yform_DATASET_IMPORTED',
                '',
                array(
                    'divider' => $div,
                    'table' => $this->table,
                    'filename' => $filename,
                    'replacefield' => $replacefield,
                    'missing_columns' => $missing_columns,
                    'debug' => $debug,
                    'data_imported' => $counter,  // importierte
                    'data_not_imported' => $dcounter, // nicht imporierte
                    'data_empty_rows' => $ecounter, // leere reihen
                    'data_replaced' => $rcounter, // replace counter
                    'data_inserted' => $icounter, // insert counter
                    'data_errors' => $errorcounter
                )
            ));

            echo rex_view::info(rex_i18n::msg('yform_manager_import_error_dataimport', ($icounter + $rcounter), $icounter, $rcounter));

        } else {

            echo rex_view::info(rex_i18n::msg('yform_manager_import_error_not_started'));

        }

        if ($dcounter > 0) {
            echo rex_view::warning(rex_i18n::msg('yform_manager_import_info_data_imported', $dcounter));

        }

    }

}







if ($show_importform) {

    ?>
    <div class="rex-addon-output"><h3 class="rex-hl2"><?php echo rex_i18n::msg('yform_manager_import_csv'); ?></h3><div class="rex-addon-content"><div id="rex-yform-import" class="yform">

    <form action="index.php" method="post" enctype="multipart/form-data">

            <p class="rex-tx1"><?php echo rex_i18n::msg('yform_manager_import_csv_info'); ?></p>

            <?php
            foreach ($this->getLinkVars() as $k => $v) {
                echo '<input type="hidden" name="' . $k . '" value="' . addslashes($v) . '" />';
            }
            ?>
            <input type="hidden" name="func" value="import" />
            <input type="hidden" name="send" value="1" />

            <?php

            echo '
            <p class="formradio formlabel-missing_columns"  id="yform-formular-missing_columns">
                <strong>' . rex_i18n::msg('yform_manager_import_if_no_column') . '</strong>
            </p>';

            $radio = new rex_radio();
            $radio->setId('missing_columns');
            $radio->setName('missing_columns');
            $radio->addOption(rex_i18n::msg('yform_manager_import_if_no_column_ignore'), '1');
            $radio->addOption(rex_i18n::msg('yform_manager_import_if_no_column_addtext'), '2');
            $radio->addOption(rex_i18n::msg('yform_manager_import_if_no_column_break'), '3');
            // $SEL->setStyle(' class="select ' . $wc . '"');
            $radio->setSelected($missing_columns);
            echo $radio->get();

            ?>

                <p class="rex-form-select">
                <label class="select " for="divider" ><?php echo rex_i18n::msg('yform_manager_import_divider'); ?></label>
                <?php
                $a = new rex_select();
                $a->setName('divider');
                $a->setId('divider');
                $a->setSize(1);
                $a->addOption(rex_i18n::msg('yform_manager_import_divider_semicolon') . ' (;)', ';');
                $a->addOption(rex_i18n::msg('yform_manager_import_divider_comma') . ' (,)', ',');
                $a->addOption(rex_i18n::msg('yform_manager_import_divider_tab') . '', 'tab');
                $a->setSelected($divider);
                echo $a->get();
                ?>
                    </p>

                <p class="rex-form-text">
                    <label for="rex-form-error-replacefield"><?php echo rex_i18n::msg('yform_manager_import_unique_field'); ?></label>
                    <input class="rex-form-text" type="text" id="rex-form-replacefield" name="replacefield" value="<?php echo htmlspecialchars(stripslashes($replacefield)); ?>" />
                </p>

                <p class="rex-form-file">
                    <label for="file_new">Datei</label>
                    <input class="rex-form-file" type="file" id="file_new" name="file_new" size="30" />
                </p>

                <p class="rex-form-submit">
                 <input class="submit" type="submit" name="save" value="<?php echo rex_i18n::msg('yform_manager_import_start'); ?>" title="<?php echo rex_i18n::msg('yform_manager_import_start'); ?>" />
                </p>

    </form>
    </div></div></div>
    <?php

}
