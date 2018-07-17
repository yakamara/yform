<?php

class rex_yform_manager_table_api
{
    public static $table_fields = ['status', 'name', 'description', 'list_amount', 'list_sortfield', 'list_sortorder', 'prio', 'search', 'hidden', 'export', 'import'];
    public static $debug = false;

    // ---------- TABLES

    public static function setTable(array $table, array $table_fields = [])
    {
        if (!isset($table['table_name'])) {
            throw new Exception('table[table_name] must be set');
        }
        $table_name = $table['table_name'];

        $currentTable = rex_yform_manager_table::get($table_name);

        if (!$currentTable) {
            // Insert
            $table_insert = rex_sql::factory();
            $table_insert->setDebug(self::$debug);
            $table_insert->setTable(rex_yform_manager_table::table());
            $table_insert->setValue('table_name', $table_name);

            if (!isset($table['name']) || $table['name'] == '') {
                $table['name'] = $table['table_name'];
            }

            foreach (self::$table_fields as $field) {
                if (isset($table[$field])) {
                    $table_insert->setValue($field, $table[$field]);
                }
            }
            if (!isset($table['prio'])) {
                $table_insert->setValue('prio', rex_yform_manager_table::getMaximumTablePrio() + 1);
            }
            $table_insert->insert();
        } else {
            $currentTable = $currentTable->toArray();

            // Update
            foreach (self::$table_fields as $field) {
                if (isset($table[$field])) {
                    $currentTable[$field] = $table[$field];
                }
            }

            if (!isset($table['name']) || $table['name'] == '') {
                $table['name'] = $table['table_name'];
            }

            $table_update = rex_sql::factory();
            $table_update->setDebug(self::$debug);
            $table_update->setTable(rex_yform_manager_table::table());
            $table_update->setWhere('table_name = :table_name', [':table_name' => $table_name]);

            foreach (self::$table_fields as $field) {
                if (isset($table[$field])) {
                    $table_update->setValue($field, $table[$field]);
                }
            }
            $table_update->update();
        }

        self::generateTablesAndFields();

        if (count($table_fields) > 0) {
            foreach ($table_fields as $field) {
                self::setTableField($table_name, $field);
            }
        }

        self::generateTablesAndFields();

        return rex_yform_manager_table::get($table_name);
    }

    public static function setTables(array $tables)
    {
        foreach ($tables as $table) {
            self::setTable($table);
        }
    }

    public static function exportTableset($table_name)
    {
        $export = [];
        $export_table = rex_yform_manager_table::get($table_name);
        $export_fields = [];
        foreach ($export_table->getFields() as $field) {
            $export_fields[] = $field->toArray();
        }

        $export[$export_table['table_name']] = [
            'table' => $export_table->toArray(),
            'fields' => $export_fields,
        ];

        return json_encode($export);
    }

    public static function importTablesets($tableset_content)
    {
        $tableset_content = json_decode($tableset_content, true);
        foreach ($tableset_content as $table) {
            if (!isset($table['table']) || !isset($table['fields'])) {
                throw new Exception('json format wrong');
            }
            $settable = $table['table'];
            $fields = $table['fields'];
            self::setTable($settable, $fields);
        }
        self::generateTablesAndFields();
        return true;
    }

    public static function exportTablesets($table_names)
    {
        $export = [];
        foreach ($table_names as $table_name) {
            $export_table = rex_yform_manager_table::get($table_name);
            $export_fields = [];
            foreach ($export_table->getFields() as $field) {
                $export_fields[] = $field->toArray();
            }
            $export[$export_table['table_name']] = [
            'table' => $export_table->toArray(),
            'fields' => $export_fields,
            ];
        }

        return json_encode($export);
    }

    public static function removeTable($table_name)
    {
        $table = rex_yform_manager_table::get($table_name);

        $t = rex_sql::factory();
        $t->setDebug(self::$debug);
        $t->setQuery('delete from ' . rex_yform_manager_table::table() . ' where table_name=:table_name ', [':table_name' => $table_name]);

        if ($table) {
            foreach ($table->getFields() as $remove_field) {
                self::removeTablefield($table_name, $remove_field->getName());
            }
        }

        rex_yform_manager_table::deleteCache();
    }

    // ---------- FIELDS

    public static function setTableField($table_name, array $table_field)
    {
        unset($table_field['id']);

        if ($table_name == '') {
            throw new Exception('table_name must be set');
        }

        if (count($table_field) == 0) {
            throw new Exception('field must be a filled array');
        }

        $fieldIdentifier = [
            'type_id' => $table_field['type_id'],
            'type_name' => $table_field['type_name'],
            'name' => $table_field['name'],
        ];

        $currentFields = rex_yform_manager_table::get($table_name)->getFields($fieldIdentifier);

        // validate specials
        if ($table_field['type_id'] == 'validate') {
            $table_field['list_hidden'] = 1;
            $table_field['search'] = 0;
        }

        self::createMissingFieldColumns($table_field);

        if (count($currentFields) > 1) {
            throw new Exception('more than one field found for table: ' . $table_name . ' with Fieldidentifier: ' . implode(', ', $fieldIdentifier) . '');
        }
        if (count($currentFields) == 0) {
            // Insert
            $field_insert = rex_sql::factory();
            $field_insert->setDebug(self::$debug);
            $field_insert->setTable(rex_yform_manager_field::table());
            $field_insert->setValue('table_name', $table_name);

            foreach ($table_field as $field_name => $field_value) {
                $field_insert->setValue($field_name, $field_value);
            }
            if (!isset($table['prio'])) {
                $field_insert->setValue('prio', rex_yform_manager_table::get($table_name)->getMaximumPrio() + 1);
            }
            $field_insert->insert();
        } else {
            // Update
            $currentField = $currentFields[0]->toArray();
            foreach ($table_field as $field_name => $field_value) {
                $currentField[$field_name] = $field_value;
            }

            $field_update = rex_sql::factory();
            $field_update->setDebug(self::$debug);
            $field_update->setTable(rex_yform_manager_field::table());

            $add_where = [];
            foreach ($fieldIdentifier as $field => $value) {
                $add_where[] = '`' . $field . '`= ' . $field_update->escape($table_name) . ' ';
            }

            $where = 'table_name=' . $field_update->escape($table_name) . '';
            if (count($add_where) > 0) {
                $where .= ' and (' . implode(' and ', $add_where) . ') ';
            }

            $field_update->setWhere($where);

            foreach ($table_field as $field_name => $field_value) {
                $field_update->setValue($field_name, $field_value);
            }
            $field_update->update();
        }

        rex_yform_manager_table::deleteCache();
    }

    public static function removeTablefield($table_name, $field_name)
    {
        $f = rex_sql::factory();
        $f->setDebug(self::$debug);
        $f->setQuery('delete from ' . rex_yform_manager_field::table() . ' where table_name=:table_name and name=:name', [':table_name' => $table_name, ':name' => $field_name]);

        rex_yform_manager_table::deleteCache();
    }

    // ---------- MIGRATION und Erstellung

    public static function migrateTable($table_name, $convert_id = false)
    {
        $columns = rex_sql::showColumns($table_name);

        if (count($columns) == 0) {
            throw new Exception('`' . $table_name . '` does not exists or no fields available');
        }

        $table = [
            'table_name' => $table_name,
            'status' => 1,
        ];

        $autoincrement = [];
        foreach ($columns as $column) {
            if ($column['extra'] == 'auto_increment') {
                $autoincrement = $column;
            }
        }

        if (count($autoincrement) > 0 && $autoincrement['name'] == 'id') {
            // everything is ok
        } elseif ($convert_id && count($autoincrement) > 1) {
            rex_sql::factory()->setQuery('ALTER TABLE `' . $table_name . '` CHANGE `' . $autoincrement['name'] . '` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ');
            $columns = rex_sql::showColumns($table_name);
        } else {
            throw new Exception('`id`-field with auto_increment is missing.');
        }

        self::setTable($table);

        foreach ($columns as $column) {
            if ($column['name'] != 'id') {
                self::migrateField($table_name, $column);
            }
        }
    }

    public static function migrateField($table_name, $column)
    {
        if ($column['name'] == 'id') {
            return [];
        }

        $fields = [];

        preg_match('@^(.*)\((.*)\)@i', $column['type'], $r);

        if (isset($r[1])) {
            $column['clean_type'] = $r[1];
            $column['length'] = $r[2];
        } else {
            $column['clean_type'] = $column['type'];
            $column['length'] = null;
        }

        switch ($column['clean_type']) {
            case 'varchar':
                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'text',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];

                $fields[] = [
                    'type_id' => 'validate',
                    'type_name' => 'size_range',
                    'name' => $column['name'],
                    'max' => $column['length'],
                    'message' => 'error: size max in ' . $column['name'] . ' is ' . $column['length'],
                ];

                if (preg_match('/(?:^|_)e?mail(?:address|adresse)?(?:_|$)/', $column['name'])) {
                    $fields[] = [
                        'type_id' => 'validate',
                        'type_name' => 'type',
                        'name' => $column['name'],
                        'type' => 'email',
                        'not_required' => 'YES' === $column['null'],
                        'message' => $column['name'] . ' must be a valid email address',
                    ];
                }

                break;

            case 'char':
                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'text',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];

                /*
                $fields[] = array(
                  'type_id' => 'validate',
                  'type_name' => 'size',
                  'name' => $column["name"],
                  'size' => $column['length'],
                  'message' => 'error: size max in '.$column["name"].' is '.$column['length']
                );
                */

                break;

            case 'enum':
                $options = array_map(function ($option) {
                    $option = trim($option, '\'" ');
                    return $option . '=' . $option;
                }, explode(',', $column['length']));

                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'select',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'options' => implode(',', $options),
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];

                break;

            case 'set':
                $options = array_map(function ($option) {
                    $option = trim($option, '\'" ');
                    return $option . '=' . $option;
                }, explode(',', $column['length']));

                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'select',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'options' => implode(',', $options),
                    'default' => (string) $column['default'],
                    'multiple' => 1,
                    'no_db' => 0,
                ];

                break;

            case 'tinyint':
                if (1 == $column['length']) {
                    $sql = rex_sql::factory();
                    $sql->setQuery('SELECT * FROM `' . $sql->escape($table_name) . '` WHERE `' . $sql->escape($column['name']) . '` NOT IN (0, 1) LIMIT 1');
                    if (!$sql->getRows()) {
                        $fields[] = [
                            'type_id' => 'value',
                            'type_name' => 'checkbox',
                            'name' => $column['name'],
                            'label' => $column['name'],
                            'default' => (string) $column['default'],
                            'no_db' => 0,
                        ];
                        break;
                    }
                }
                // no break
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'text',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];

                $fields[] = [
                    'type_id' => 'validate',
                    'type_name' => 'type',
                    'name' => $column['name'],
                    'type' => 'int',
                    'not_required' => 'YES' === $column['null'],
                    'message' => $column['name'] . ' must be an integer',
                ];

                break;

            case 'float':
            case 'double':
            case 'decimal':
                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'text',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];

                $fields[] = [
                    'type_id' => 'validate',
                    'type_name' => 'type',
                    'name' => $column['name'],
                    'type' => 'float',
                    'not_required' => 'YES' === $column['null'],
                    'message' => $column['name'] . ' must be a float',
                ];
                break;

            case 'date':
                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'date',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];
                break;

            case 'time':
                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'time',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];
                break;

            case 'datetime':
            case 'timestamp':
                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'datetime',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];
                break;

            case 'blob':
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'year':
            case 'binary':
            case 'varbinary':
                // do nothing.
                break;

            case 'text':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            default:
                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'textarea',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];
                break;
        }

        foreach ($fields as $field) {
            self::setTableField($table_name, $field);
        }
    }

    public static function createMissingFieldColumns($field)
    {
        $columns = [];
        foreach (rex_sql::showColumns(rex_yform_manager_field::table()) as $column) {
            $columns[$column['name']] = true;
        }

        $alterTable = [];
        foreach ($field as $column => $value) {
            if (!isset($columns[$column])) {
                $alterTable[] = 'ADD `' . $column . '` TEXT NOT NULL';
            }
            $columns[$column] = true;
        }

        if (count($alterTable)) {
            $alter = rex_sql::factory();
            $alter->setDebug(self::$debug);
            $alter->setQuery('ALTER TABLE `' .  rex_yform_manager_field::table() . '` ' . implode(',', $alterTable));
        }

        rex_yform_manager_table::deleteCache();
    }

    public static function generateTablesAndFields($delete_old = false)
    {
        rex_yform_manager_table::deleteCache();
        $types = rex_yform::getTypeArray();
        foreach (rex_yform_manager_table::getAll() as $table) {
            $c = rex_sql::factory();
            $c->setDebug(self::$debug);
            $c->setQuery('CREATE TABLE IF NOT EXISTS `' . $table['table_name'] . '` ( `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

            // remember fields, create and in case delete
            $c->setQuery('SHOW COLUMNS FROM `' . $table['table_name'] . '`');
            $saved_columns = $c->getArray();

            $EnsureTable = rex_sql_table::get($table['table_name']);

            foreach ($table->getFields() as $field) {
                $type_name = $field['type_name'];
                $type_id = $field['type_id'];

                if ($type_id == 'value') {
                    $type_label = $field['name'];
                    $dbtype = $types[$type_id][$type_name]['dbtype'];

                    if ($dbtype != 'none' && $dbtype != '') {
                        if (isset($types[$type_id][$type_name]['hooks']['preCreate'])) {
                            $result = call_user_func($types[$type_id][$type_name]['hooks']['preCreate'], $field);
                            if (false === $result) {
                                continue;
                            }
                            if (is_string($result)) {
                                $dbtype = $result;
                            }
                        }
                        $add_column = true;
                        foreach ($saved_columns as $uu => $vv) {
                            if ($vv['Field'] == $type_label) {
                                $add_column = false;
                                $null = isset($types[$type_id][$type_name]['null']) && $types[$type_id][$type_name]['null'];
                                $EnsureTable
                                    ->ensureColumn(new rex_sql_column($type_label, $dbtype, $null))
                                    ->ensure();
                                unset($saved_columns[$uu]);
                                break;
                            }
                        }

                        if ($add_column) {
                            $null = isset($types[$type_id][$type_name]['null']) && $types[$type_id][$type_name]['null'];
                            $null = $null ? '' : ' NOT NULL';
                            $c->setQuery('ALTER TABLE `' . $table['table_name'] . '` ADD `' . $type_label . '` ' . $dbtype . $null);
                        }
                    }

                }
            }

            if ($delete_old === true) {
                foreach ($saved_columns as $uu => $vv) {
                    if ($vv['Field'] != 'id') {
                        $c->setQuery('ALTER TABLE `' . $table['table_name'] . '` DROP `' . $vv['Field'] . '` ');
                    }
                }
            }
        }

        rex_yform_manager_table::deleteCache();
    }
}
