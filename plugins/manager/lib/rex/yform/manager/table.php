<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_manager_table implements ArrayAccess
{
    protected $values = array();

    /** @type rex_yform_manager_field[] */
    protected $fields = array();

    protected static $debug = false;

    /** @type self[] */
    protected static $tables = array();
    protected static $loadedAllTables = false;

    public function __construct(array $values)
    {
        if (count($values) == 0) {
            throw new Exception(rex_i18n::msg('yform_table_not_found'));
        }
        $this->values = $values;

        $tb = rex_sql::factory();
        if (self::$debug) {
            $tb->setDebug();
        }
        $tb->setQuery('select * from ' . rex_yform_manager_field::table() . ' where table_name=' . $tb->escape($this->getTablename()) . ' order by prio');

        $this->fields = array();
        foreach ($tb->getArray() as $field) {
            $this->fields[] = new rex_yform_manager_field($field);
        }
    }

    public static function get($table_name)
    {
        if (isset(self::$tables[$table_name])) {
            return self::$tables[$table_name];
        }

        $tb = rex_sql::factory();
        if (self::$debug) {
            $tb->setDebug();
        }
        $tables = $tb->getArray('select * from ' . self::table() . ' where table_name = ' . $tb->escape($table_name) . '');

        if (count($tables) != 1) {
            return null;
        }
        return self::$tables[$table_name] = new self($tables[0]);
    }

    public static function reload()
    {
        self::$tables = array();
        self::$loadedAllTables = false;
    }

    public static function getAll()
    {
        if (self::$loadedAllTables) {
            return self::$tables;
        }
        self::$loadedAllTables = true;

        $table_array = rex_sql::factory();
        if (self::$debug) {
            $table_array->setDebug();
        }
        $table_array = $table_array->getArray('select * from ' . self::table() . ' order by prio');

        self::$tables = array();
        foreach ($table_array as $t) {
            self::$tables[$t['table_name']] = new self($t);
        }
        return self::$tables;
    }

    public static function table()
    {
        global $REX;
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

    public function getId()
    {
        return $this->values['id'];
    }

    public function hasId()
    {
        $columns = rex_sql::showColumns($this->getTableName());
        foreach ($columns as $column) {
            if ($column['name'] == 'id' && $column['extra'] == 'auto_increment') {
                return true;
            }
        }
        return false;
    }

    public function isActive()
    {
        return $this->values['status'] == 1;
    }

    public function isHidden()
    {
        return $this->values['hidden'] == 1;
    }

    public function isSearchable()
    {
        return $this->values['search'] == 1;
    }

    public function isImportable()
    {
        return $this->values['import'] == 1;
    }

    public function isExportable()
    {
        return $this->values['export'] == 1;
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


    public function getDescription()
    {
        return $this->values['description'];
    }

    /**
     * Fields of yform Definitions
     *
     * @param array $filter
     * @return rex_yform_manager_field[]
     */
    public function getFields(array $filter = array())
    {
        if (!$filter) {
            return $this->fields;
        }
        $fields = array();
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
     * @param array $filter
     * @return rex_yform_manager_field[]
     */
    public function getValueFields(array $filter = array())
    {
        $fields = array();
        foreach ($this->fields as $field) {
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
        $fields = $this->getValueFields(array('name' => $name));
        return isset($fields[$name]) ? $fields[$name] : null;
    }

    /**
     * @return rex_yform_manager_field[]
     */
    public function getRelations()
    {
        return $this->getValueFields(array('type_name' => 'be_manager_relation'));
    }

    /**
     * @param string $table
     * @return rex_yform_manager_field[]
     */
    public function getRelationsTo($table)
    {
        return $this->getValueFields(array('type_name' => 'be_manager_relation', 'table' => $table));
    }

    /**
     * @param string $column
     * @return rex_yform_manager_field
     */
    public function getRelation($column)
    {
        $relations = $this->getRelations();
        return isset($relations[$column]) ? $relations[$column] : null;
    }

    // Database Fielddefinition
    public function getColumns()
    {
        $columns = rex_sql::showColumns($this->getTableName());
        $c = array();
        foreach ($columns as $column) {
            $c[$column['name']] = $column;
        }
        unset($c['id']);
        return $c;
    }

    public function getMissingFields()
    {
        $xfields = $this->getValueFields();
        $rfields = self::getColumns();

        $c = array();
        foreach ($rfields as $k => $v) {
            if (!array_key_exists($k, $xfields)) {
                $c[$k] = $k;
            }
        }
        return $c;
    }


    public function getPermKey()
    {
        return 'yform[table:' . $this->getTableName() . ']';
    }

    public function toArray()
    {
        return $this->values;
    }


    public function removeRelationTableRelicts()
    {
        $deleteSql = rex_sql::factory();
        foreach ($this->getValueFields(array('type_name' => 'be_manager_relation')) as $field) {
            if ($field->getElement('relation_table')) {
                $table = self::get($field->getElement('relation_table'));
                $source = $table->getRelationsTo($this->getTableName());
                if (!empty($source)) {
                    $relationTable = $deleteSql->escape($field->getElement('relation_table'));
                    $deleteSql->setQuery('
                        DELETE FROM `' . $relationTable . '`
                        WHERE NOT EXISTS (SELECT * FROM `' . $this->getTableName() . '` WHERE id = ' . $relationTable . '.`' . $deleteSql->escape(reset($source)->getName()) . '`)
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

    // ------------------------------------------- Array Access
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->values[$offset];
    }

    public function __toString()
    {
        return $this->getTableName();
    }

}
