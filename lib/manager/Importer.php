<?php

namespace Redaxo\YForm\Manager;

use InvalidArgumentException;
use rex_extension;
use rex_extension_point;
use rex_i18n;
use rex_sql;
use rex_string;
use rex_yform_manager_field;
use rex_yform_manager_table;
use rex_yform_manager_table_api;
use RuntimeException;
use Throwable;

use function array_key_exists;
use function count;
use function in_array;

class Importer
{
    public const DELIMITER_OPTIONS = [
        'semicolon' => ';',
        'comma' => ',',
        'tab' => '\t',
    ];
    public const MISSING_COLUMNS_MODE_IGNORE = 1; // ignore missing fields
    public const MISSING_COLUMNS_MODE_ADD = 2; // add field if missing
    public const MISSING_COLUMNS_MODE_ERROR = 3; // error if fields are missing

    public const MISSING_COLUMNS_OPTIONS = [
        self::MISSING_COLUMNS_MODE_IGNORE => 'yform_manager_import_missing_columns_ignore',
        self::MISSING_COLUMNS_MODE_ADD => 'yform_manager_import_missing_columns_add',
        self::MISSING_COLUMNS_MODE_ERROR => 'yform_manager_import_missing_columns_error',
    ];
    private string $delimiter = ';';
    private rex_yform_manager_table $table;
    private string $filePath = '';
    private array $messages = [];
    private ?int $missing_columns_mode = null;

    public function __construct(rex_yform_manager_table $table)
    {
        $this->table = $table;
    }

    public function setMissingColumnsMode(int $mode): void
    {
        if (!in_array($mode, array_keys(self::MISSING_COLUMNS_OPTIONS), true)) {
            throw new InvalidArgumentException('Invalid missing columns mode: ' . $mode);
        }
        $this->missing_columns_mode = $mode;
    }

    public function getMissingColumnsMode(): int
    {
        return $this->missing_columns_mode ?? self::MISSING_COLUMNS_MODE_IGNORE;
    }

    public function setTable(rex_yform_manager_table $table): void
    {
        $this->table = $table;
    }

    public function getTable(): rex_yform_manager_table
    {
        return $this->table;
    }

    public function setDelimiter(string $delimiter, $force = false): void
    {
        if (!$force && !in_array($delimiter, self::DELIMITER_OPTIONS, true)) {
            if (isset(self::DELIMITER_OPTIONS[$delimiter])) {
                $delimiter = self::DELIMITER_OPTIONS[$delimiter];
            } else {
                $delimiter = ',';
            }
        }
        $this->delimiter = $delimiter;
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function setImportFilePath(string $filePath): void
    {
        if (!is_file($filePath) && !is_readable($filePath)) {
            throw new InvalidArgumentException('File not found or not readable: ' . $filePath);
        }
        $this->filePath = $filePath;
    }

    public function getImportFilePath(): string
    {
        if ('' === $this->filePath) {
            throw new RuntimeException('Import file path is not set.');
        }
        return $this->filePath;
    }

    public function setMessage($message, $type = 'info'): void
    {
        $this->messages[$type][] = $message;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function hasErrors()
    {
        return isset($this->messages['error']) && count($this->messages['error']) > 0;
    }

    public function import()
    {
        $fieldarray = [];

        $fields = [];
        foreach ($this->table->getFields() as $field) {
            $fields[strtolower($field->getName())] = $field;
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
                'divider' => $this->getDelimiter(),
                'table' => $this->getTable(),
                'filename' => $this->getImportFilePath(),
                'missing_columns' => $this->getMissingColumnsMode(),
                'importer' => $this,
            ],
        ));

        if ($import_start) {
            $error_message = null;
            try {
                $fp = fopen($this->getImportFilePath(), 'r');
                $firstbytes = fread($fp, 3);
                $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
                if ($bom != $firstbytes) {
                    rewind($fp);
                }

                // out of transaction, because database not always supports transactions with alter table
                $idColumn = null;
                while (false !== ($line_array = fgetcsv($fp, 30384, $this->getDelimiter()))) {
                    if (0 == count($fieldarray)) {
                        $fieldarray = $line_array;
                        $fieldarray = array_map('rex_string::normalize', $fieldarray);

                        if (in_array('', $fieldarray)) {
                            $this->setMessage(
                                rex_i18n::msg('yform_manager_import_error_emptyfielddefinition'),
                                'error',
                            );
                            break;
                        }

                        if (count($fieldarray) != count(array_unique($fieldarray))) {
                            $this->setMessage(
                                rex_i18n::msg('yform_manager_import_error_duplicatefielddefinition'),
                                'error',
                            );
                            break;
                        }

                        $mc = [];
                        foreach ($fieldarray as $k => $v) {
                            $v = rex_string::normalize($v);
                            $fieldarray[$k] = $v;
                            if (!array_key_exists($fieldarray[$k], $fields) && 'id' != $fieldarray[$k]) {
                                $mc[$fieldarray[$k]] = $fieldarray[$k];
                            }
                            if ('id' === $fieldarray[$k]) {
                                $idColumn = $k;
                            }
                        }

                        if (count($mc) > 0) {
                            switch ($this->getMissingColumnsMode()) {
                                case 3:
                                    $this->setMessage(
                                        rex_i18n::msg(
                                            'yform_manager_import_error_missingfields',
                                            implode(', ', $mc),
                                        ),
                                        'error',
                                    );
                                    break;
                                case 2:
                                    foreach ($mc as $mcc) {
                                        rex_sql::factory()
                                            ->setTable(rex_yform_manager_field::table())
                                            ->setValue('table_name', $this->getTable()->getTablename())
                                            ->setValue('prio', 999)
                                            ->setValue('type_id', 'value')
                                            ->setValue('type_name', 'text')
                                            ->setValue('name', $mcc)
                                            ->setValue('label', 'TEXT `' . $mcc . '`')
                                            ->setValue('list_hidden', 0)
                                            ->setValue('db_type', 'text')
                                            ->insert();
                                        $this->setMessage(
                                            rex_i18n::msg('yform_manager_import_field_added', $mcc),
                                            'info',
                                        );
                                    }
                                    rex_yform_manager_table_api::generateTablesAndFields();

                                    $fields = [];
                                    foreach (rex_yform_manager_table::get(
                                        $this->getTable()->getTableName(),
                                    )->getFields() as $field) {
                                        $fields[strtolower($field->getName())] = $field;
                                    }
                                    break;
                                case 1:
                                default:
                                    if (count($fieldarray) == count($mc)) {
                                        $this->setMessage(
                                            rex_i18n::msg(
                                                'yform_manager_import_error_min_missingfields',
                                                implode(', ', $mc),
                                            ),
                                            'error',
                                        );
                                        break;
                                    }

                                    foreach ($fieldarray as $k => $name) {
                                        if (isset($mc[$name])) {
                                            unset($fieldarray[$k]);
                                        }
                                    }
                                    break;
                            }
                        }
                        break;
                    }
                }

                $sql_db = rex_sql::factory();
                $sql_db->transactional(function () use ($fp, $idColumn, $fieldarray, &$counter, &$dcounter, &$ecounter, &$rcounter, &$icounter, &$errorcounter) {
                    while (false !== ($line_array = fgetcsv($fp, 30384, $this->getDelimiter()))) {
                        if (!$line_array) {
                            break;
                        }

                        if (null !== $idColumn && isset($line_array[$idColumn]) && $line_array[$idColumn] > 0) {
                            $id = $line_array[$idColumn];
                            $dataset = $this->table->getRawDataset((int) $id);
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
                                if ('' == $msg) {
                                    $msg = rex_i18n::msg('yform_values_message_is_missing', '', $key);
                                } else {
                                    $msg = rex_i18n::translate($msg);
                                }
                                $messages[$key] = $msg;
                            }
                            ++$dcounter;
                            $dataId = 'ID: ' . $id;
                            $this->setMessage(rex_i18n::msg('yform_manager_import_error_dataimport', $dataId, '<br />* ' . implode('<br />* ', $messages)), 'error');
                        } elseif ($exists) {
                            ++$rcounter;
                        } else {
                            ++$icounter;
                        }
                    }

                    rex_extension::registerPoint(new rex_extension_point(
                        'YFORM_DATASET_IMPORTED',
                        '',
                        [
                            'divider' => $this->getDelimiter(),
                            'table' => $this->getTable(),
                            'filename' => $this->getImportFilePath(),
                            'missing_columns' => $this->getMissingColumnsMode(),
                            'data_imported' => $counter,  // importierte
                            'data_not_imported' => $dcounter, // nicht imporierte
                            'data_empty_rows' => $ecounter, // leere reihen
                            'data_replaced' => $rcounter, // replace counter
                            'data_inserted' => $icounter, // insert counter
                            'data_errors' => $errorcounter,
                        ],
                    ));
                });
            } catch (Throwable $e) {
                dump($e);
                $error_message = $e->getMessage();
            }

            if ($error_message) {
                $this->setMessage(rex_i18n::msg('yform_manager_import_error_import_abort', $error_message), 'error');
            } else {
                $this->setMessage(rex_i18n::msg('yform_manager_import_error_import', $icounter + $rcounter, $icounter, $rcounter), 'info');
            }
        } else {
            $this->setMessage(rex_i18n::msg('yform_manager_import_error_not_started'), 'info');
        }

        if ($dcounter > 0) {
            $this->setMessage(rex_i18n::msg('yform_manager_import_info_data_imported', $dcounter), 'error');
        }

        rex_yform_manager_table::deleteCache();
    }
}
