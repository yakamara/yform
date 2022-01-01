<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_manager_table implements ArrayAccess
{
    protected $values = [];

    protected $columns = [];

    /** @var rex_yform_manager_field[] */
    protected $fields = [];

    /** @var rex_yform_manager_field[] */
    protected $relations;

    protected static $debug = false;

    /** @var self[] */
    protected static $tables = [];
    protected static $loadedAllTables = false;

    private static $cache;
    protected $relatedTableNames = [];
    protected $fieldValues = [];

    private function __construct(array $data)
    {
        $this->values = $data['table'];
        $this->columns = $data['columns'];
        $this->relatedTableNames = $data['related_tables'];
        $this->fieldValues = $data['fields'];
    }

    /**
     * @param string $tableName
     *
     * @return null|rex_yform_manager_table
     */
    public static function get($tableName)
    {
        if (isset(self::$tables[$tableName])) {
            return self::$tables[$tableName];
        }

        $cache = self::getCache();

        if (!isset($cache[$tableName])) {
            unset(self::$tables[$tableName]);
            return null;
        }

        return self::$tables[$tableName] = new self($cache[$tableName]);
    }

    public static function require(string $tableName): self
    {
        $table = self::get($tableName);

        if (!$table) {
            throw new rex_exception('Table "'.$tableName.'" does not exist');
        }

        return $table;
    }

    /**
     * @param int $tableId
     *
     * @return rex_yform_manager_table|null
     */
    public static function getById(int $tableID)
    {
        $tables = self::getAll();

        foreach ($tables as $table) {
            if ($table->getId() == $tableID) {
                return self::get($table->getTableName());
            }
        }

        return null;
    }

    /**
     * @return rex_yform_manager_table[]
     */
    public static function getAll()
    {
        if (self::$loadedAllTables) {
            return self::$tables;
        }

        self::$loadedAllTables = true;

        $tables = self::$tables;
        self::$tables = [];
        foreach (self::getCache() as $tableName => $table) {
            self::$tables[$tableName] = $tables[$tableName] ?? new self($table);
        }

        return self::$tables;
    }

    public static function table()
    {
        return rex::getTablePrefix() . 'yform_table';
    }

    // -------------------------------------------------------------------------

    public function getTableName()
    {
        return $this->values['table_name'];
    }

    public function getName()
    {
        return $this->values['name'];
    }

    public function getNameLocalized()
    {
        $table_name = $this->getTableName();
        $name = $this->getName();
        if ($name === $table_name) {
            $name = 'translate:'.$name;
        }
        $name = rex_i18n::translate($name);
        if (preg_match('/^\[translate:(.*?)\]$/', $name, $match)) {
            $name = $match[1];
        }
        return \rex_i18n::translate($name);
    }

    public function getId()
    {
        return $this->values['id'];
    }

    public function hasId()
    {
        $columns = rex_sql::showColumns($this->getTableName());
        foreach ($columns as $column) {
            if ('id' == $column['name'] && 'auto_increment' == $column['extra']) {
                return true;
            }
        }
        return false;
    }

    public function isActive()
    {
        return 1 == $this->values['status'];
    }

    public function isHidden()
    {
        return 1 == $this->values['hidden'];
    }

    public function isSearchable()
    {
        return 1 == $this->values['search'];
    }

    public function isImportable()
    {
        return 1 == $this->values['import'];
    }

    public function isExportable()
    {
        return 1 == $this->values['export'];
    }

    public function isMassDeletionAllowed()
    {
        return 1 == $this->values['mass_deletion'];
    }

    public function isMassEditAllowed()
    {
        return 1 == $this->values['mass_edit'];
    }

    public function overwriteSchema()
    {
        return (1 == $this->values['schema_overwrite']) ? true : false;
    }

    public function hasHistory()
    {
        return 1 == $this->values['history'];
    }

    public function getSortFieldName()
    {
        return $this->values['list_sortfield'];
    }

    public function getSortOrderName()
    {
        return $this->values['list_sortorder'];
    }

    public function getListAmount()
    {
        if (!isset($this->values['list_amount']) || $this->values['list_amount'] < 1) {
            $this->values['list_amount'] = 30;
        }
        return $this->values['list_amount'];
    }

    public function getDescription(): string
    {
        return $this->values['description'];
    }

    /**
     * Fields of yform Definitions.
     *
     * @return rex_yform_manager_field[]
     */
    public function getFields(array $filter = [])
    {
        if (0 == count($this->fields)) {
            foreach ($this->fieldValues as $field) {
                try {
                    $this->fields[] = new rex_yform_manager_field($field);
                } catch (Exception $e) {
                    // ignore missing fields
                }
            }
        }

        if (!$filter) {
            return $this->fields;
        }
        $fields = [];
        foreach ($this->fields as $field) {
            foreach ($filter as $key => $value) {
                if ($value != $field->getElement($key)) {
                    continue 2;
                }
            }
            $fields[] = $field;
        }
        return $fields;
    }

    /**
     * @return rex_yform_manager_field[]
     */
    public function getValueFields(array $filter = [])
    {
        $fields = [];
        foreach ($this->getFields() as $field) {
            if ('value' !== $field->getType()) {
                continue;
            }
            foreach ($filter as $key => $value) {
                if ($value != $field->getElement($key)) {
                    continue 2;
                }
            }
            $fields[$field->getName()] = $field;
        }
        return $fields;
    }

    public function getValueField($name)
    {
        $fields = $this->getValueFields(['name' => $name]);
        return $fields[$name] ?? null;
    }

    /**
     * @return rex_yform_manager_field[]
     */
    public function getRelations()
    {
        if (null === $this->relations) {
            $this->relations = $this->getValueFields(['type_name' => 'be_manager_relation']);
        }

        return $this->relations;
    }

    /**
     * @param string $table
     *
     * @return rex_yform_manager_field[]
     */
    public function getRelationsTo($table)
    {
        return $this->getValueFields(['type_name' => 'be_manager_relation', 'table' => $table]);
    }

    /**
     * @param string $column
     *
     * @return rex_yform_manager_field|null
     */
    public function getRelation(string $column)
    {
        $relations = $this->getRelations();
        return $relations[$column] ?? null;
    }

    public function getRelationTableColumns($column)
    {
        $relation = $this->getRelation($column);

        $table = self::get($relation['relation_table']);
        $source = $table->getRelationsTo($this->getTableName());
        $target = $table->getRelationsTo($relation['table']);

        if (!$source || !$target) {
            throw new RuntimeException(sprintf('Missing relation column in relation table "%s"', $relation['relation_table']));
        }

        $source = reset($source)->getName();
        $target = reset($target)->getName();

        return ['source' => $source, 'target' => $target];
    }

    public function getRelationTableNames(): array
    {
        return $this->relatedTableNames;
    }

    // Database Fielddefinition
    public function getColumns()
    {
        return $this->columns;
    }

    public function getMissingFields(): array
    {
        $xfields = $this->getValueFields();
        $rfields = self::getColumns();

        $c = [];
        foreach ($rfields as $k => $v) {
            if (!array_key_exists($k, $xfields)) {
                $c[$k] = $k;
            }
        }
        return $c;
    }

    public function toArray()
    {
        return $this->values;
    }

    public function removeRelationTableRelicts()
    {
        $deleteSql = rex_sql::factory();
        foreach ($this->getValueFields(['type_name' => 'be_manager_relation']) as $field) {
            if ($field->getElement('relation_table')) {
                $table = self::get($field->getElement('relation_table'));
                $source = $table->getRelationsTo($this->getTableName());
                if (!empty($source)) {
                    $relationTable = $deleteSql->escapeIdentifier($field->getElement('relation_table'));
                    $deleteSql->setQuery('
                        DELETE FROM ' . $relationTable . '
                        WHERE NOT EXISTS (SELECT * FROM ' . $deleteSql->escapeIdentifier($this->getTableName()) . ' WHERE id = ' . $relationTable . '.' . $deleteSql->escapeIdentifier(reset($source)->getName()) . ')
                    ');
                }
            }
        }
    }

    public static function getMaximumTablePrio()
    {
        $sql = 'select max(prio) as prio from ' . self::table() . '';
        $gf = rex_sql::factory();
        if (self::$debug) {
            $gf->setDebug();
        }
        $gf->setQuery($sql);
        return $gf->getValue('prio');
    }

    public function getMaximumPrio()
    {
        $sql = 'select max(prio) as prio from ' . rex_yform_manager_field::table() . ' where table_name="' . $this->getTableName() . '"';
        $gf = rex_sql::factory();
        if (self::$debug) {
            $gf->setDebug();
        }
        $gf->setQuery($sql);
        return $gf->getValue('prio');
    }

    /**
     * @return rex_yform_manager_dataset
     */
    public function createDataset()
    {
        return rex_yform_manager_dataset::create($this->getTableName());
    }

    /**
     * @param int $id
     *
     * @return null|rex_yform_manager_dataset
     */
    public function getDataset($id)
    {
        return rex_yform_manager_dataset::get($id, $this->getTableName());
    }

    /**
     * @param int $id
     *
     * @return rex_yform_manager_dataset
     */
    public function getRawDataset($id)
    {
        return rex_yform_manager_dataset::getRaw($id, $this->getTableName());
    }

    /**
     * @return rex_yform_manager_query
     */
    public function query()
    {
        return new rex_yform_manager_query($this->getTableName());
    }

    // ------------------------------------------- Array Access
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    #[ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->values[$offset];
    }

    public function __toString()
    {
        return $this->getTableName();
    }

    public static function deleteCache()
    {
        rex_file::delete(self::cachePath());
        self::$cache = null;
        self::$tables = [];
        self::$loadedAllTables = false;
    }

    private static function getCache()
    {
        if (null !== self::$cache) {
            return self::$cache;
        }

        $cachePath = self::cachePath();
        self::$cache = rex_file::getCache($cachePath);
        if (self::$cache) {
            return self::$cache;
        }

        self::$cache = [];

        $sql = rex_sql::factory();
        $sql->setDebug(self::$debug);

        $tables = $sql->getArray('select * from ' . self::table() . ' order by prio');
        foreach ($tables as $table) {
            $tableName = (string) $table['table_name'];
            self::$cache[$tableName]['table'] = $table;
            self::$cache[$tableName]['columns'] = [];
            try {
                foreach (rex_sql::showColumns($tableName) as $column) {
                    if ('id' !== $column['name']) {
                        self::$cache[$tableName]['columns'][$column['name']] = $column;
                    }
                }
            } catch (Exception $e) {
            }

            self::$cache[$tableName]['fields'] = [];
        }

        $fields = $sql->getArray('select * from ' . rex_yform_manager_field::table() . ' order by prio');
        foreach ($fields as $field) {
            if (isset(self::$cache[(string) $field['table_name']])) {
                self::$cache[(string) $field['table_name']]['fields'][] = $field;
            }
        }

        foreach (self::$cache as $tableName => $data) {
            self::$cache[(string) $tableName]['related_tables'] = [];
            $table = new self(self::$cache[(string) $tableName]);
            foreach ($table->getFields() as $field) {
                foreach ($field->getRelationTableNames() as $relatedTable) {
                    self::$cache[(string) $tableName]['related_tables'][$relatedTable] = $relatedTable;
                }
            }
        }

        rex_file::putCache($cachePath, self::$cache);

        return self::$cache;
    }

    private static function cachePath()
    {
        return rex_path::pluginCache('yform', 'manager', 'tables.cache');
    }

    public function isGranted($type, rex_user $user)
    {
        return rex_yform_manager_table_authorization::onAttribute($type, $this, $user);
    }

    public function getCSRFKey()
    {
        return 'table_field-'.$this->getTableName();
    }
}
