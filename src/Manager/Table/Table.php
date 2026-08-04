<?php

namespace Yakamara\YForm\Manager\Table;

use Override;
use Redaxo\Core\Exception\RuntimeException as RexRuntimeException;
use ArrayAccess;
use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Exception\Exception;
use Redaxo\Core\Filesystem\File;
use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\Security\User;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Fragment;
use RuntimeException;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Manager\Query;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class Table implements ArrayAccess
{
    public static array $tableLayouts = [];
    public static string $defaultTableLayout = 'yform/manager/page/layout.php';

    protected $values = [];
    protected $columns = [];

    /** @var array<Field> */
    protected $fields = [];

    /** @var array<Field> */
    protected $relations;

    protected static bool $debug = false;

    /** @var array<self> */
    protected static $tables = [];
    protected static bool $loadedAllTables = false;

    private static $cache;
    protected array $relatedTableNames = [];
    protected array $fieldValues = [];

    private function __construct(array $data)
    {
        $this->values = $data['table'];
        $this->columns = $data['columns'];
        $this->relatedTableNames = $data['related_tables'];
        $this->fieldValues = $data['fields'];
    }

    public static function setTableLayout(string $tableName, string $path): void
    {
        self::$tableLayouts[$tableName] = $path;
    }

    public function getTableLayout(): string
    {
        $tableLayout = self::$defaultTableLayout;
        if (isset(self::$tableLayouts[$this->getTableName()])) {
            $tableLayout = self::$tableLayouts[$this->getTableName()];
        }
        return $tableLayout;
    }

    /**
     * @param string $tableName
     *
     * @return Table|null
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
            throw new RexRuntimeException('Table "' . $tableName . '" does not exist');
        }

        return $table;
    }

    /**
     * @return Table|null
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
     * @return array<Table>
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

    public static function table(): string
    {
        return Core::getTablePrefix() . 'yform_table';
    }

    // -------------------------------------------------------------------------

    public function getTableName(): string
    {
        return $this->values['table_name'];
    }

    public function getName(): string
    {
        return $this->values['name'];
    }

    public function getNameLocalized(): string
    {
        $table_name = $this->getTableName();
        $name = $this->getName();
        if ($name === $table_name) {
            $name = 'translate:' . $name;
        }
        $name = I18n::translate($name, false);
        if (preg_match('/^\[translate:(.*?)\]$/', $name, $match)) {
            $name = $match[1];
        }
        return I18n::translate($name, false);
    }

    public function getId()
    {
        return $this->values['id'];
    }

    public function hasId(): bool
    {
        $columns = Sql::showColumns($this->getTableName());
        foreach ($columns as $column) {
            if ('id' == $column['name'] && 'auto_increment' == $column['extra']) {
                return true;
            }
        }
        return false;
    }

    public function isActive(): bool
    {
        return 1 == $this->values['status'];
    }

    public function isHidden(): bool
    {
        return 1 == $this->values['hidden'];
    }

    public function isSearchable(): bool
    {
        return 1 == $this->values['search'];
    }

    public function isImportable(): bool
    {
        return 1 == $this->values['import'];
    }

    public function isExportable(): bool
    {
        return 1 == $this->values['export'];
    }

    public function isMassDeletionAllowed(): bool
    {
        return 1 == $this->values['mass_deletion'];
    }

    public function isMassEditAllowed(): bool
    {
        return 1 == $this->values['mass_edit'];
    }

    public function overwriteSchema(): bool
    {
        return (1 == $this->values['schema_overwrite']) ? true : false;
    }

    public function hasHistory(): bool
    {
        return 1 == $this->values['history'];
    }

    public function parseLayout(Fragment $fragment): string
    {
        return $fragment->parse($this->getTableLayout());
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
            $this->values['list_amount'] = 100;
        }
        return $this->values['list_amount'];
    }

    public function getDescription(): string
    {
        // The column is nullable and a table created through the API or a tableset
        // import need not carry one — only the backend form always writes ''.
        return $this->values['description'] ?? '';
    }

    public function getCustomIcon(): ?string
    {
        return $this->values['table_icon'];
    }

    /**
     * Fields of yform Definitions.
     *
     * @return array<Field>
     */
    public function getFields(array $filter = [])
    {
        if (0 == count($this->fields)) {
            foreach ($this->fieldValues as $field) {
                try {
                    $this->fields[] = new Field($field);
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
     * @return array<Field>
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
     * @return array<Field>
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
     * @return array<Field>
     */
    public function getRelationsTo($table)
    {
        return $this->getValueFields(['type_name' => 'be_manager_relation', 'table' => $table]);
    }

    /**
     * @return Field|null
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
        $deleteSql = Sql::factory();
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
        $gf = Sql::factory();
        if (self::$debug) {
            $gf->setDebug();
        }
        $gf->setQuery($sql);
        return $gf->getValue('prio');
    }

    public function getMaximumPrio()
    {
        $sql = 'select max(prio) as prio from ' . Field::table() . ' where table_name="' . $this->getTableName() . '"';
        $gf = Sql::factory();
        if (self::$debug) {
            $gf->setDebug();
        }
        $gf->setQuery($sql);
        return $gf->getValue('prio');
    }

    /**
     * @return Dataset
     */
    public function createDataset()
    {
        return Dataset::create($this->getTableName());
    }

    /**
     * @param int $id
     *
     * @return Dataset|null
     */
    public function getDataset($id)
    {
        return Dataset::get($id, $this->getTableName());
    }

    /**
     * @param int $id
     *
     * @return Dataset
     */
    public function getRawDataset($id)
    {
        return Dataset::getRaw($id, $this->getTableName());
    }

    /**
     * @return Query
     */
    public function query()
    {
        return new Query($this->getTableName());
    }

    // ------------------------------------------- Array Access
    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null === $offset) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->values[$offset]);
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset];
    }

    public function __toString()
    {
        return $this->getTableName();
    }

    public static function deleteCache(): void
    {
        File::delete(self::cachePath());
        self::$cache = null;
        self::$tables = [];
        self::$loadedAllTables = false;
    }

    private static function getCache(): mixed
    {
        if (null !== self::$cache) {
            return self::$cache;
        }

        $cachePath = self::cachePath();
        self::$cache = File::getCache($cachePath);
        if (self::$cache) {
            return self::$cache;
        }

        self::$cache = [];

        $sql = Sql::factory();
        $sql->setDebug(self::$debug);

        $tables = $sql->getArray('select * from ' . self::table() . ' order by prio');
        foreach ($tables as $table) {
            $tableName = (string) $table['table_name'];
            self::$cache[$tableName]['table'] = $table;
            self::$cache[$tableName]['columns'] = [];
            try {
                foreach (Sql::showColumns($tableName) as $column) {
                    if ('id' !== $column['name']) {
                        self::$cache[$tableName]['columns'][$column['name']] = $column;
                    }
                }
            } catch (Exception $e) {
            }

            self::$cache[$tableName]['fields'] = [];
        }

        $fields = $sql->getArray('select * from ' . Field::table() . ' order by prio');
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

        File::putCache($cachePath, self::$cache);

        return self::$cache;
    }

    private static function cachePath(): string
    {
        // REDAXO 6 dropped the plugin concept, so what used to be the manager plugin's cache directory
        // is now a file inside the addon's own cache directory.
        return Path::addonCache('yform', 'manager.tables.cache');
    }

    public function isGranted(string $type, User $user): bool
    {
        return Authorization::onAttribute($type, $this, $user);
    }

    public function getCSRFKey(): string
    {
        return 'table_field-' . $this->getTableName();
    }
}
