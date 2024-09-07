<?php

class rex_yform_manager_dataset
{
    use rex_instance_pool_trait;

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    private static bool $debug = false;

    /** @var array<string, class-string<self>> */
    private static array $tableToModel = [];

    /** @var array<class-string<self>, string> */
    private static array $modelToTable = [];

    private string $table;

    /** @var int|null */
    private $id;

    private bool $exists = false;

    /** @var array<string, mixed> */
    private array $data;

    private bool $dataLoaded = false;

    /** @var array<string, rex_yform_manager_collection> */
    private array $relatedCollections = [];

    /** @var string[] */
    private array $messages = [];

    /** @var bool */
    private $historyEnabled = true;

    final private function __construct(string $table, ?int $id = null)
    {
        $this->table = $table;
        $this->id = $id;
    }

    /**
     * @return static
     */
    public static function create(?string $table = null): self
    {
        $table = $table ?: static::modelToTable();
        /** @var class-string<static> $class */
        $class = self::tableToModel($table);
        $dataset = new $class($table);
        $dataset->data = [];
        $dataset->dataLoaded = true;
        $dataset->exists = false;

        return $dataset;
    }

    /** @return null|static */
    public static function get(int $id, ?string $table = null): ?self
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(sprintf('$id has to be an integer greater than 0, but "%s" given', $id));
        }

        $table = $table ?: static::modelToTable();

        $class = self::getModelClass($table);
        if ($class && __CLASS__ === static::class) {
            /* @noinspection PhpUndefinedMethodInspection */
            return $class::get($id, $table);
        }

        /* @phpstan-ignore-next-line */
        return static::getInstance([$table, $id], static function ($table, $id) {
            return static::query($table)->findId($id);
        });
    }

    /**
     * @throws rex_exception if dataset does not exist
     * @return static
     */
    public static function require(int $id, ?string $table = null): self
    {
        $dataset = self::get($id, $table);

        if (!$dataset) {
            $table = $table ?: static::modelToTable();

            throw new rex_exception('Dataset with ID "' . $id . '" does not exist in "' . $table . '"');
        }

        return $dataset;
    }

    /**
     * @return static
     */
    public static function getRaw(int $id, ?string $table = null): self
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(sprintf('$id has to be an integer greater than 0, but "%s" given', $id));
        }

        $table = $table ?: static::modelToTable();

        $class = self::getModelClass($table);
        if ($class && __CLASS__ === static::class) {
            /* @noinspection PhpUndefinedMethodInspection */
            return $class::getRaw($id, $table);
        }

        /* @phpstan-ignore-next-line */
        return static::getInstance([$table, $id], static function ($table, $id) {
            $class = self::tableToModel($table);
            return new $class($table, $id);
        });
    }

    /**
     * @return rex_yform_manager_collection<static>
     */
    public static function getAll(?string $table = null): rex_yform_manager_collection
    {
        return static::query($table)->find();
    }

    public static function table(): rex_yform_manager_table
    {
        $class = static::class;

        if (__CLASS__ === $class || !isset(self::$modelToTable[$class])) {
            throw new RuntimeException(sprintf('Method "%s()" is only callable for registered model classes.', __METHOD__));
        }

        return rex_yform_manager_table::require(self::$modelToTable[$class]);
    }

    /**
     * @return rex_yform_manager_query<static>
     */
    public static function query(?string $table = null): rex_yform_manager_query
    {
        return rex_yform_manager_query::get($table ?: static::modelToTable());
    }

    /**
     * @return null|static
     */
    public static function queryOne(string $query, array $params = [], ?string $table = null): ?self
    {
        $table = $table ?: static::modelToTable();

        $class = self::getModelClass($table);
        if ($class && __CLASS__ === static::class) {
            /* @noinspection PhpUndefinedMethodInspection */
            return $class::queryOne($query, $params, $table);
        }

        $sql = rex_sql::factory();
        $sql
            ->setDebug(self::$debug)
            ->setQuery($query, $params);

        if (!$sql->getRows()) {
            return null;
        }

        $data = [];
        foreach ($sql->getFieldnames() as $key) {
            $data[$key] = $sql->getValue($key);
        }

        return static::fromSqlData($data, $table);
    }

    /**
     * @return rex_yform_manager_collection<static>
     */
    public static function queryCollection(string $query, array $params = [], ?string $table = null): rex_yform_manager_collection
    {
        $table = $table ?: static::modelToTable();

        $class = self::getModelClass($table);
        if ($class && __CLASS__ === static::class) {
            /* @noinspection PhpUndefinedMethodInspection */
            return $class::queryCollection($query, $params, $table);
        }

        $sql = rex_sql::factory();
        $sql->setDebug(self::$debug);

        $data = $sql->getArray($query, $params);

        $datasets = [];
        foreach ($data as $row) {
            $datasets[] = static::fromSqlData($row, $table);
        }

        return new rex_yform_manager_collection($table, $datasets);
    }

    /**
     * @param class-string<self> $modelClass
     */
    public static function setModelClass(string $table, string $modelClass): void
    {
        self::$tableToModel[$table] = $modelClass;
        self::$modelToTable[$modelClass] = $table;
    }

    /**
     * @return null|class-string<static>
     */
    public static function getModelClass(string $table): ?string
    {
        return self::$tableToModel[$table] ?? null; // @phpstan-ignore-line
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getTable(): rex_yform_manager_table
    {
        return rex_yform_manager_table::require($this->table);
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new rex_exception('Calling getId() on new, non-existing datasets is not allowed, check existence before by $dataset->exists()');
        }

        return $this->id;
    }

    public function exists(): bool
    {
        if (!$this->dataLoaded) {
            $this->loadData();
        }

        return $this->exists;
    }

    public function hasValue(string $key): bool
    {
        if (!$this->dataLoaded) {
            $this->loadData();
        }

        return array_key_exists($key, $this->data);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue(string $key, $value): self
    {
        if (!$this->dataLoaded) {
            $this->loadData();
        }

        $this->data[$key] = $value;
        unset($this->relatedCollections[$key]);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue(string $key)
    {
        if ('id' === $key) {
            return $this->id;
        }

        if (!$this->dataLoaded) {
            $this->loadData();
        }

        return $this->data[$key];
    }

    public function getData(): array
    {
        if (!$this->dataLoaded) {
            $this->loadData();
        }

        return $this->data;
    }

    public function loadData(): void
    {
        $sql = rex_sql::factory();
        $rows = $sql->getArray('SELECT * FROM `' . $this->table . '` WHERE id = ? LIMIT 1', [$this->id]);
        $this->exists = isset($rows[0]);
        if ($this->exists) {
            $this->data = $rows[0];
        } else {
            $this->data = [];
        }
        $this->dataLoaded = true;
        $this->relatedCollections = [];
    }

    public function invalidateData(): void
    {
        $this->dataLoaded = false;
        $this->data = [];
        $this->exists = false;
        $this->relatedCollections = [];
    }

    public function getRelatedDataset(string $key): ?self
    {
        $relation = $this->getTable()->getRelation($key);

        if (!$relation) {
            throw new InvalidArgumentException(sprintf('Field "%s" in table "%s" is not a relation field.', $key, $this->getTableName()));
        }

        $id = $this->getValue($key);

        if (!$id) {
            return null;
        }

        // php-cs-fixer would replace `rex_yform_manager_dataset::get()` by `self::get()`
        // but it would not work in this case, so we are using `__CLASS__`.
        $class = __CLASS__;

        /* @noinspection PhpUndefinedMethodInspection */
        return $class::get($id, $relation['table']);
    }

    public function getRelatedCollection(string $key): rex_yform_manager_collection
    {
        if (isset($this->relatedCollections[$key])) {
            return $this->relatedCollections[$key];
        }

        $query = $this->getRelatedQuery($key);

        return $this->relatedCollections[$key] = $query->find();
    }

    /**
     * @return $this
     *
     * @internal
     */
    public function setRelatedCollection(string $key, rex_yform_manager_collection $collection): self
    {
        $this->relatedCollections[$key] = $collection;

        return $this;
    }

    public function getRelatedQuery(string $key): rex_yform_manager_query
    {
        $relation = $this->getTable()->getRelation($key);

        if (!$relation) {
            throw new InvalidArgumentException(sprintf('Field "%s" in table "%s" is not a relation field.', $key, $this->getTableName()));
        }

        $query = self::query($relation['table']);

        if (0 == $relation['type'] || 2 == $relation['type']) {
            $query->where('id', $this->getValue($key));
        } elseif (4 == $relation['type'] || 5 == $relation['type']) {
            $query->where($relation['field'], $this->getId());
        } elseif (empty($relation['relation_table'])) {
            $query->where('id', explode(',', $this->getValue($key)));
        } else {
            $columns = $this->getTable()->getRelationTableColumns($key);
            $query
                ->join($relation['relation_table'], null, $relation['relation_table'] . '.' . $columns['target'], $relation['table'] . '.id')
                ->where($relation['relation_table'] . '.' . $columns['source'], $this->getId());
        }

        return $query;
    }

    public function isValid(): bool
    {
        $yform = $this->getInternalForm();
        $this->setFormMainId($yform);
        $yform->initializeFields();

        $table = $this->getTable();
        $fields = $table->getValueFields();
        foreach ($this->data as $key => $value) {
            if ('id' === $key) {
                continue;
            }
            if (isset($fields[$key])) {
                $yform->objparams['data'][$key] = $value;
            }
        }

        $yform->setFieldValue('send', [], '1');

        $yform->executeFields();
        $this->messages = $yform->getObjectparams('warning_messages');

        return empty($this->messages);
    }

    public function save(): bool
    {
        $yform = $this->getInternalForm();
        $this->setFormMainId($yform);
        $yform->initializeFields();

        $table = $this->getTable();
        $fields = $table->getValueFields();
        $columns = $table->getColumns();
        foreach ($this->data as $key => $value) {
            if ('id' === $key) {
                $yform->objparams['value_pool']['sql'][$key] = $value;
                continue;
            }
            if (isset($fields[$key])) {
                $yform->objparams['data'][$key] = $value;
            } elseif (isset($columns[$key])) {
                $yform->objparams['value_pool']['sql'][$key] = $value;
            }
        }

        $yform->setFieldValue('send', [], '1');
        $this->executeForm($yform);
        $this->messages = $yform->getObjectparams('warning_messages');

        return empty($this->messages);
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function delete(): bool
    {
        if (!rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_DELETE', true, ['table' => $this->getTable(), 'data_id' => $this->id, 'data' => $this]))) {
            return false;
        }

        if ($this->getTable()->hasHistory()) {
            $this->makeSnapshot(self::ACTION_DELETE);
        }

        $sql = rex_sql::factory();
        $sql
            ->setDebug(self::$debug)
            ->setTable($this->table)
            ->setWhere(['id' => $this->id])
            ->delete();

        $this->getTable()->removeRelationTableRelicts();

        rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_DELETED', '', ['table' => $this->getTable(), 'data_id' => $this->id, 'data' => $this]));

        self::clearInstance([$this->getTable()->getName(), $this->id]);

        $this->invalidateData();
        $this->dataLoaded = true;

        return true;
    }

    /**
     * Fields of yform Definitions.
     *
     * @return rex_yform_manager_field[]
     */
    public function getFields(array $filter = []): array
    {
        return $this->getTable()->getFields($filter);
    }

    public function getForm(): rex_yform
    {
        $yform = $this->createForm();
        $this->setFormMainId($yform);

        return $yform;
    }

    /**
     * @param null|callable(rex_yform):void $afterFieldsExecuted
     */
    public function executeForm(rex_yform $yform, ?callable $afterFieldsExecuted = null): string
    {
        $exists = $this->exists();
        $oldData = $this->getData();

        if ($exists) {
            /** @var rex_yform $yform */
            $yform = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_UPDATE', $yform, ['table' => $this->getTable(), 'data_id' => $this->id, 'data' => $this]));
        } else {
            /** @var rex_yform $yform */
            $yform = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_ADD', $yform, ['table' => $this->getTable(), 'data' => $this]));
        }

        $yform->setObjectparams('manager_dataset', $this);
        $yform->executeFields();

        if ($afterFieldsExecuted) {
            call_user_func($afterFieldsExecuted, $yform);
        }

        if (!$this->id) {
            rex_extension::register('YFORM_SAVED', function (rex_extension_point $ep) {
                if ($ep->getSubject() instanceof Exception) {
                    return;
                }

                /** @var rex_yform_action_db $dbAction */
                $dbAction = $ep->getParam('form');
                if ($dbAction->getParam('manager_dataset') !== $this) {
                    return;
                }

                $this->id = ((int) $dbAction->getParam('main_id')) ?: null;
                if ($this->id) {
                    self::addInstance($this->id, $this);
                    rex_yform_value_be_manager_relation::clearCache($this->table);
                }
            }, rex_extension::EARLY);
        }

        $form = $yform->executeActions();

        if ($yform->objparams['actions_executed']) {
            if ($exists) {
                rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_UPDATED', $yform, ['table' => $this->getTable(), 'data_id' => $this->id, 'data' => $this, 'old_data' => $oldData]));
            } else {
                rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_ADDED', $yform, ['table' => $this->getTable(), 'data_id' => $this->id, 'data' => $this]));
            }
        }

        return $form;
    }

    /**
     * @param self::ACTION_* $action
     */
    public function makeSnapshot(string $action): void
    {
        if (!in_array($action, [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE])) {
            throw new InvalidArgumentException(sprintf('Unknown action "%s", allowed actions are %s::ACTION_CREATE, ::ACTION_UPDATE and ::ACTION_DELETE', $action, __CLASS__));
        }

        $user = rex::getEnvironment();
        if ('backend' == $user && $rexUser = rex::getUser()) {
            $user = $rexUser->getLogin();
        }
        // ep to overwrite user
        $user = rex_extension::registerPoint(new rex_extension_point('YCOM_HISTORY_USER', $user));

        $sql = rex_sql::factory();
        $sql->setDebug(self::$debug);
        $sql
            ->setTable(rex::getTable('yform_history'))
            ->setValue('table_name', $this->table)
            ->setValue('dataset_id', $this->id)
            ->setValue('action', $action)
            ->setValue('user', $user)
            ->setValue('timestamp', $sql::datetime())
            ->insert();

        $historyId = $sql->getLastId();

        $sql
            ->setTable($this->table)
            ->setWhere(['id' => $this->id])
            ->select();

        $inserts = [];
        foreach ($this->getFields() as $field) {
            $fieldName = $field->getName();
            $fieldObject = $field->getObject();

            if ('id' === $fieldName) {
                continue;
            }

            $value = @$sql->getValue($fieldName);
            if ('rex_yform_value_be_manager_relation' == $fieldObject::class && !$value) {
                $collection = $this->getRelatedCollection($fieldName);
                $values = [];
                foreach ($collection as $item) {
                    $values[] = $item->getId();
                }
                $value = implode(',', $values);
            }

            if ('value' == $field->getType() && null !== $value) {
                $inserts[] = sprintf(
                    '(%d, %s, %s)',
                    $historyId,
                    $sql->escape($fieldName),
                    $sql->escape((string) $value),
                );
            }
        }

        $sql->setQuery('INSERT INTO ' . rex::getTable('yform_history_field') . ' (`history_id`, `field`, `value`) VALUES ' . implode(', ', $inserts));
    }

    public function restoreSnapshot(int $snapshotId): bool
    {
        $sql = rex_sql::factory();
        $sql->setDebug(self::$debug);
        $values = [];
        foreach ($sql->getArray(sprintf('SELECT * FROM %s WHERE history_id = %d', rex::getTable('yform_history_field'), $snapshotId)) as $value) {
            $values[$value['field']] = $value['value'];
        }

        foreach ($this->getFields() as $field) {
            $fieldName = $field->getName();
            if ('value' == $field->getType() && isset($values[$fieldName])) {
                $this->setValue($fieldName, $values[$fieldName]);
            }
        }

        return $this->save();
    }

    public function isHistoryEnabled(): bool
    {
        return $this->historyEnabled;
    }

    public function setHistoryEnabled(bool $historyEnabled): void
    {
        $this->historyEnabled = $historyEnabled;
    }

    public function __isset(string $key): bool
    {
        return $this->hasValue($key);
    }

    /**
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getValue($key);
    }

    /**
     * @param mixed $value
     */
    public function __set(string $key, $value): void
    {
        $this->setValue($key, $value);
    }

    private function getInternalForm(): rex_yform
    {
        $dummy = new static($this->table, 0);

        $yform = $dummy->createForm();
        $yform->setObjectparams('real_field_names', true);
        $yform->setObjectparams('form_needs_output', false);
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('get_field_type', '');

        return $yform;
    }

    private function createForm(): rex_yform
    {
        $yform = new rex_yform();
        $fields = $this->getFields();
        $yform->setDebug(self::$debug);

        foreach ($fields as $field) {
            /** @var class-string<rex_yform_base_abstract> $class */
            $class = 'rex_yform_' . $field->getType() . '_' . $field->getTypeName();

            /** @var rex_yform_base_abstract $cl */
            $cl = new $class();
            $definitions = $cl->getDefinitions();

            $values = [];
            $i = 1;
            foreach ($definitions['values'] as $key => $_) {
                $values[] = $field->getElement($key);
                ++$i;
            }

            if ('value' == $field->getType()) {
                $yform->setValueField($field->getTypeName(), $values);
            } elseif ('validate' == $field->getType()) {
                $yform->setValidateField($field->getTypeName(), $values);
            } elseif ('action' == $field->getType()) {
                $yform->setActionField($field->getTypeName(), $values);
            }
        }

        $yform->setObjectparams('main_table', $this->table);
        $yform->setActionField('db', [$this->table, 'main_where']);

        return $yform;
    }

    private function setFormMainId(rex_yform $yform): void
    {
        if ($this->exists()) {
            $where = 'id = ' . (int) $this->id;
            $yform->setObjectparams('main_id', $this->id);
            $yform->setObjectparams('main_where', $where);
        } elseif ($this->id) {
            $yform->objparams['value_pool']['sql']['id'] = $this->id;
        }
    }

    /**
     * @return class-string<self>
     */
    private static function tableToModel(string $table): string
    {
        return self::getModelClass($table) ?: __CLASS__;
    }

    /**
     * @internal
     */
    protected static function modelToTable(): string
    {
        $class = static::class;

        if (isset(self::$modelToTable[$class])) {
            return self::$modelToTable[$class];
        }

        if (__CLASS__ === $class) {
            throw new RuntimeException('Missing $table argument');
        }

        throw new RuntimeException(sprintf('Missing $table declaration for model class "%s"', $class));
    }

    /**
     * @return static
     */
    final protected static function fromSqlData(array $data, string $table): self
    {
        $id = (int) $data['id'];
        $class = self::tableToModel($table);

        /** @var static $dataset */
        $dataset = new $class($table, $id);
        self::addInstance([$table, $id], $dataset);

        $dataset->dataLoaded = true;
        $dataset->exists = true;
        $dataset->data = $data;

        return $dataset;
    }
}
