<?php

class rex_yform_manager_table_api
{
    public static $table_fields = ['status', 'name', 'description', 'list_amount', 'list_sortfield', 'list_sortorder', 'prio', 'search', 'hidden', 'export', 'import', 'schema_overwrite'];
    public static $debug = false;

    /**
     * @throws rex_sql_exception
     */
    public static function setTable(array $table, array $table_fields = []): ?rex_yform_manager_table
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

            if (!isset($table['name']) || '' == $table['name']) {
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

            foreach (self::$table_fields as $field) {
                if (isset($table[$field])) {
                    $currentTable[$field] = $table[$field];
                }
            }

            if (!isset($table['name']) || '' == $table['name']) {
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

    /**
     * @throws rex_sql_exception
     */
    public static function setTables(array $tables)
    {
        foreach ($tables as $table) {
            self::setTable($table);
        }
    }

    /**
     * @throws rex_sql_exception
     */
    public static function importTablesets(string $tableset_content): bool
    {
        $tableset_content = json_decode($tableset_content, true);
        foreach ($tableset_content as $table) {
            if (!isset($table['table']) || !isset($table['fields'])) {
                throw new Exception('json format wrong');
            }
            $settable = $table['table'];
            $fields = $table['fields'];
            $settable['schema_overwrite'] = 1;
            self::setTable($settable, $fields);
        }
        self::generateTablesAndFields();
        return true;
    }

    /**
     * @return false|string
     */
    public static function exportTablesets(array $table_names)
    {
        $export = [];
        foreach ($table_names as $table_name) {
            $export_table = rex_yform_manager_table::get($table_name);
            $export_fields = [];
            foreach ($export_table->getFields() as $field) {
                $export_fields[] = array_diff_key($field->toArray(), ['id' => 0]);
            }
            $export[$export_table['table_name']] = [
                'table' => array_diff_key($export_table->toArray(), ['id' => 0, 'prio' => 0]),
                'fields' => $export_fields,
            ];
        }

        return json_encode($export);
    }

    /**
     * @throws rex_sql_exception
     */
    public static function removeTable(string $table_name)
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

    /**
     * @throws rex_sql_exception
     */
    public static function setTableField(string $table_name, array $table_field)
    {
        unset($table_field['id']);

        if ('' == $table_name) {
            throw new Exception('table_name must be set');
        }

        if (0 == count($table_field)) {
            throw new Exception('field must be a filled array');
        }

        $fieldIdentifier = [];
        $fieldIdentifier['type_id'] = $table_field['type_id'];
        $fieldIdentifier['name'] = $table_field['name'];
        if ('validate' == $fieldIdentifier['type_id']) {
            $fieldIdentifier['type_name'] = $table_field['type_name'];
        }

        $currentFields = rex_yform_manager_table::get($table_name)->getFields($fieldIdentifier);

        // validate specials
        if ('validate' == $table_field['type_id']) {
            $table_field['list_hidden'] = 1;
            $table_field['search'] = 0;
        }

        self::createMissingFieldColumns($table_field);

        if (count($currentFields) > 1) {
            throw new Exception('more than one field found for table: ' . $table_name . ' with Fieldidentifier: ' . implode(', ', $fieldIdentifier) . '');
        }
        if (0 == count($currentFields)) {
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
                $add_where[] = '`' . $field . '`= ' . $field_update->escape($value) . ' ';
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

    /**
     * @throws rex_sql_exception
     */
    public static function removeTablefield(string $table_name, string $field_name)
    {
        $f = rex_sql::factory();
        $f->setDebug(self::$debug);
        $f->setQuery('delete from ' . rex_yform_manager_field::table() . ' where table_name=:table_name and name=:name', [':table_name' => $table_name, ':name' => $field_name]);

        rex_yform_manager_table::deleteCache();
    }

    /**
     * @throws rex_sql_exception
     */
    public static function migrateTable(string $table_name, bool $schema_overwrite = false)
    {
        $columns = rex_sql::showColumns($table_name);

        if (0 == count($columns)) {
            throw new Exception('`' . $table_name . '` does not exists or no fields available');
        }

        $table = [
            'table_name' => $table_name,
            'status' => 1,
            'schema_overwrite' => $schema_overwrite ? 1 : 0,
        ];

        $error = true;
        foreach ($columns as $column) {
            if ('auto_increment' == $column['extra'] && 'id' == $column['name']) {
                $error = false;
            }
        }

        if ($error) {
            throw new Exception('`id`-field with auto_increment is missing.');
        }

        self::setTable($table);

        foreach ($columns as $column) {
            if ('id' != $column['name']) {
                self::migrateField($table_name, $column);
            }
        }
    }

    /**
     * @param $table_name
     * @param $column
     * @throws rex_sql_exception
     * @return array
     */
    public static function migrateField($table_name, $column)
    {
        if ('id' == $column['name']) {
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
                $options = array_map(static function ($option) {
                    $option = trim($option, '\'" ');
                    return $option . '=' . $option;
                }, explode(',', $column['length']));

                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'choice',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'choices' => implode(',', $options),
                    'default' => (string) $column['default'],
                    'no_db' => 0,
                ];

                break;

            case 'set':
                $options = array_map(static function ($option) {
                    $option = trim($option, '\'" ');
                    return $option . '=' . $option;
                }, explode(',', $column['length']));

                $fields[] = [
                    'type_id' => 'value',
                    'type_name' => 'choice',
                    'name' => $column['name'],
                    'label' => $column['name'],
                    'choices' => implode(',', $options),
                    'default' => (string) $column['default'],
                    'multiple' => 1,
                    'no_db' => 0,
                ];

                break;

            case 'tinyint':
                if (1 == $column['length']) {
                    $sql = rex_sql::factory();
                    $sql->setQuery('SELECT * FROM ' . $sql->escapeIdentifier($table_name) . ' WHERE ' . $sql->escapeIdentifier($column['name']) . ' NOT IN (0, 1) LIMIT 1');
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
            case 'json'
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

    /**
     * @param $field
     * @throws rex_sql_exception
     */
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

    /**
     * @param false                   $delete_old
     * @throws rex_sql_exception
     */
    public static function generateTableAndFields(rex_yform_manager_table $table, $delete_old = false)
    {
        $tableName = $table->getTableName();
        rex_yform_manager_table::deleteCache();

        $table = rex_yform_manager_table::get($tableName);
        if (!$table || !$table->overwriteSchema()) {
            return;
        }

        $c = rex_sql::factory();
        $c->setDebug(self::$debug);
        $c->setQuery('CREATE TABLE IF NOT EXISTS `' . $table->getTableName() . '` ( `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $c->setQuery('ALTER TABLE `' . $table->getTableName() . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

        // remember fields, create and in case delete
        $savedColumns = $table->getColumns();

        $EnsureTable = rex_sql_table::get($table->getTableName());

        $EnsureTable
            ->ensurePrimaryIdColumn();

        foreach ($table->getFields() as $field) {
            if ('value' == $field->getType()) {
                $db_type = $field->getDatabaseFieldType();

                if ('none' != $db_type && '' != $db_type) {
                    $hooks = $field->getHooks();
                    if (isset($hooks['preCreate'])) {
                        $result = call_user_func($hooks['preCreate'], $field, $db_type);
                        if (false === $result) {
                            continue;
                        }
                        if (is_string($result)) {
                            $db_type = $result;
                        }
                    }

                    $default = $field->getDatabaseFieldDefault();
                    if (isset($hooks['preDefault'])) {
                        $result = call_user_func($hooks['preDefault'], $field, $default);
                        if (is_string($result)) {
                            $default = $result;
                        }
                    }

                    $existingColumn = false;

                    foreach ($savedColumns as $savedColumn) {
                        if ($savedColumn['name'] == $field->getName()) {
                            unset($savedColumns[$savedColumn['name']]);
                            $existingColumn = true;
                            break;
                        }
                    }

                    if (!$existingColumn || ($existingColumn && $table->overwriteSchema())) {
                        $EnsureTable
                        ->ensureColumn(new rex_sql_column($field->getName(), $db_type, $field->getDatabaseFieldNull(), $default));
                    }
                }
            }
        }

        $EnsureTable
            ->ensure();

        if (true === $delete_old) {
            foreach ($savedColumns as $savedColumn) {
                if ('id' != $savedColumn['name']) {
                    $c->setQuery('ALTER TABLE `' . $table->getTableName() . '` DROP `' . $savedColumn['name'] . '` ');
                }
            }
        }
        rex_yform_manager_table::deleteCache();
    }

    /**
     * @param false $delete_old
     * @throws rex_sql_exception
     */
    public static function generateTablesAndFields($delete_old = false)
    {
        rex_yform_manager_table::deleteCache();
        foreach (rex_yform_manager_table::getAll() as $table) {
            self::generateTableAndFields($table, $delete_old);
        }
        rex_yform_manager_table::deleteCache();
    }
}
