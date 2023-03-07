<?php

/**
 * @template T of rex_yform_manager_dataset
 * @implements IteratorAggregate<int, T>
 */
class rex_yform_manager_query implements IteratorAggregate, Countable
{
    /** @var string */
    private $table;

    /** @var string|null */
    private $alias;
    /** @var bool */
    private $selectResetted = false;
    /** @var list<string> */
    private $select = [];

    /** @var list<string> */
    private $joins = [];

    /** @var 'AND'|'OR' */
    private $whereOperator = 'AND';
    /** @var list<string> */
    private $where = [];
    /** @var array<string, int|string> */
    private $params = [];
    /** @var int */
    private $paramCounter = 1;

    /** @var list<string> */
    private $orderBy = [];
    /** @var bool */
    private $orderByResetted = false;

    /** @var list<string> */
    private $groupBy = [];

    /** @var string|null */
    private $limit;

    final public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function __toString(): string
    {
        return $this->getQuery();
    }

    /**
     * @return static
     */
    public static function get(string $table): self
    {
        return new static($table);
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getTable(): rex_yform_manager_table
    {
        return rex_yform_manager_table::require($this->table);
    }

    /**
     * @return $this
     */
    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getTableAlias(): string
    {
        return $this->alias ?: $this->table;
    }

    /**
     * @return $this
     */
    public function resetSelect(): self
    {
        $this->select = [];
        $this->selectResetted = true;

        return $this;
    }

    /**
     * @param string|list<string> $column
     * @return $this
     */
    public function select($column, ?string $alias = null): self
    {
        if (!is_array($column)) {
            return $this->selectRaw($this->quoteIdentifier($column), $alias);
        }

        $this->resetSelect();
        foreach ($column as $s) {
            $this->select($s);
        }

        return $this;
    }

    /**
     * @param string|list<string> $expression
     * @return $this
     */
    public function selectRaw($expression, ?string $alias = null): self
    {
        if (is_array($expression)) {
            $this->resetSelect();
            $this->select = $expression;

            return $this;
        }

        if ($alias) {
            $expression .= ' AS '.$alias;
        }
        $this->select[] = $expression;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetJoins()
    {
        $this->joins = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function joinRelation(string $column, ?string $alias = null): self
    {
        return $this->joinTypeRelation('INNER', $column, $alias);
    }

    /**
     * @return $this
     */
    public function leftJoinRelation(string $column, ?string $alias = null): self
    {
        return $this->joinTypeRelation('LEFT', $column, $alias);
    }

    /**
     * @param string $type "INNER", "LEFT", "RIGHT"...
     * @return $this
     */
    public function joinTypeRelation(string $type, string $column, ?string $alias = null): self
    {
        $relation = $this->getTable()->getRelation($column);

        if (!$relation) {
            throw new InvalidArgumentException(sprintf('Column "%s" is not a be_manager_relation column', $column));
        }

        $relatedTable = $alias ?: $relation['table'];

        if (4 == $relation['type'] || 5 == $relation['type']) {
            return $this->joinType($type, $relation['table'], $alias, $this->getTableAlias().'.id', $relatedTable.'.'.$relation['field']);
        }

        $relatedField = $relatedTable.'.id';

        if (empty($relation['relation_table'])) {
            $column = $this->getTableAlias().'.'.$column;
            $operator = in_array($relation['type'], [1, 3]) ? 'FIND_IN_SET' : '=';

            return $this->joinType($type, $relation['table'], $alias, $relatedField, $column, $operator);
        }

        $relationColumns = $this->getTable()->getRelationTableColumns($column);

        return $this
            ->joinType($type, $relation['relation_table'], null, $this->getTableAlias().'.id', $relation['relation_table'].'.'.$relationColumns['source'])
            ->joinType($type, $relation['table'], $alias, $relation['relation_table'].'.'.$relationColumns['target'], $relatedField);
    }

    /**
     * @return $this
     */
    public function join(string $table, ?string $alias = null, ?string $column1 = null, ?string $column2 = null, ?string $operator = null): self
    {
        return $this->joinType('INNER', $table, $alias, $column1, $column2, $operator);
    }

    /**
     * @return $this
     */
    public function leftJoin(string $table, ?string $alias = null, ?string $column1 = null, ?string $column2 = null, ?string $operator = null): self
    {
        return $this->joinType('LEFT', $table, $alias, $column1, $column2, $operator);
    }

    /**
     * @param string $type "INNER", "LEFT", "RIGHT"...
     * @return $this
     */
    public function joinType(string $type, string $table, ?string $alias = null, ?string $column1 = null, ?string $column2 = null, ?string $operator = null): self
    {
        $condition = null;
        if ($column1 && $column2) {
            $column1 = $this->quoteIdentifier($column1);
            $column2 = $this->quoteIdentifier($column2);
            $operator = $operator ? mb_strtoupper($operator) : '=';
            if ('FIND_IN_SET' === $operator) {
                $condition = sprintf('FIND_IN_SET(%s, %s)', $column1, $column2);
            } else {
                $condition = sprintf('%s %s %s', $column1, $operator, $column2);
            }
        }

        return $this->joinRaw($type, $table, $alias, $condition);
    }

    /**
     * @param string $type "INNER", "LEFT", "RIGHT"...
     * @return $this
     */
    public function joinRaw(string $type, string $table, ?string $alias = null, ?string $condition = null): self
    {
        $join = sprintf('%s JOIN `%s`', mb_strtoupper($type), $table);
        if ($alias) {
            $join .= ' AS '.$alias;
        }
        if ($condition) {
            $join .= ' ON '.$condition;
        }
        $this->joins[] = $join;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetWhere(): self
    {
        $this->where = [];
        $this->params = [];
        $this->paramCounter = 1;

        return $this;
    }

    /**
     * @param 'AND'|'OR' $operator
     * @return $this
     */
    public function setWhereOperator(string $operator): self
    {
        $this->whereOperator = $operator;

        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function where(string $column, $value, ?string $operator = null): self
    {
        if (is_array($value)) {
            $param = [];
            foreach ($value as $v) {
                $param[] = $this->addParam($v);
            }
            $param = '('.implode(', ', $param).')';

            $operator = $operator ?: 'IN';
        } else {
            $param = $this->addParam($value);

            $operator = $operator ?: '=';
        }

        $this->where[] = sprintf('%s %s %s', $this->quoteIdentifier($column), $operator, $param);

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function whereNot(string $column, $value): self
    {
        $operator = is_array($value) ? 'NOT IN' : '!=';

        return $this->where($column, $value, $operator);
    }

    /**
     * @return $this
     */
    public function whereNull(string $column): self
    {
        $this->where[] = $this->quoteIdentifier($column).' IS NULL';

        return $this;
    }

    /**
     * @return $this
     */
    public function whereNotNull(string $column): self
    {
        $this->where[] = $this->quoteIdentifier($column).' IS NOT NULL';

        return $this;
    }

    /**
     * @param mixed  $from
     * @param mixed  $to
     * @return $this
     */
    public function whereBetween(string $column, $from, $to): self
    {
        $this->where[] = sprintf('%s BETWEEN %s AND %s', $this->quoteIdentifier($column), $this->addParam($from), $this->addParam($to));

        return $this;
    }

    /**
     * @param mixed $from
     * @param mixed $to
     * @return $this
     */
    public function whereNotBetween(string $column, $from, $to): self
    {
        $this->where[] = sprintf('%s NOT BETWEEN %s AND %s', $this->quoteIdentifier($column), $this->addParam($from), $this->addParam($to));

        return $this;
    }

    /**
     * Where the comma separated list column contains the given value or any of the given values.
     *
     * @param string           $column Column with comma separated list
     * @param string|int|int[] $value  Single value (string or int) or array of values (ints only)
     * @return $this
     */
    public function whereListContains(string $column, $value): self
    {
        if (!is_array($value)) {
            $this->where[] = sprintf('FIND_IN_SET(%s, %s)', $this->addParam($value), $this->quoteIdentifier($column));

            return $this;
        }

        $regex = '(^|,)('.implode('|', array_map('intval', $value)).')(,|$)';
        $this->where[] = sprintf('%s REGEXP %s', $this->quoteIdentifier($column), $this->addParam($regex));

        return $this;
    }

    /**
     * @param array<string, int|string> $params
     * @return $this
     */
    public function whereRaw(string $where, array $params = []): self
    {
        $this->where[] = $where;
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * @param array<string, mixed>|callable(self<T>):void $nested
     * @param 'AND'|'OR' $operator
     * @return $this
     */
    public function whereNested($nested, string $operator = 'AND'): self
    {
        /** @var 'AND'|'OR' $operator */
        $operator = strtoupper(trim($operator));

        if (is_array($nested)) {
            $this->where[] = $this->buildNestedWhere($nested, $operator);

            return $this;
        }

        $query = new self($this->table);
        $query->whereOperator = $operator;
        $query->paramCounter = $this->paramCounter;

        call_user_func($nested, $query);

        if ($query->where) {
            $this->where[] = '('.implode(' '.$operator.' ', $query->where).')';
            $this->params = array_merge($this->params, $query->params);
            $this->paramCounter = $query->paramCounter;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function resetGroupBy()
    {
        $this->groupBy = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function groupBy(string $column): self
    {
        $this->groupBy[] = $this->quoteIdentifier($column);

        return $this;
    }

    /**
     * @return $this
     */
    public function groupByRaw(string $expression): self
    {
        $this->groupBy[] = $expression;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetOrderBy(): self
    {
        $this->orderBy = [];
        $this->orderByResetted = true;

        return $this;
    }

    /**
     * @param 'ASC'|'DESC'|'asc'|'desc' $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = $this->quoteIdentifier($column).' '.$direction;

        return $this;
    }

    /**
     * @param 'ASC'|'DESC' $direction
     * @return $this
     */
    public function orderByRaw(string $expression, string $direction = 'ASC'): self
    {
        $this->orderBy[] = $expression.' '.$direction;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetLimit(): self
    {
        $this->limit = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function limit(int $offsetOrRowCount, ?int $rowCount = null): self
    {
        $this->limit = $rowCount ? $offsetOrRowCount.', '.$rowCount : (string) $offsetOrRowCount;

        return $this;
    }

    public function getQuery(): string
    {
        $select = $this->select;
        if (!$this->selectResetted) {
            array_unshift($select, sprintf('`%s`.*', $this->alias ?: $this->table));
        }

        $query = sprintf('SELECT %s FROM `%s`', implode(', ', $select), $this->table);

        if ($this->alias) {
            $query .= ' AS '.$this->alias;
        }

        if ($this->joins) {
            $query .= ' '.implode(' ', $this->joins);
        }

        if ($this->where) {
            $query .= ' WHERE '.implode(' '.$this->whereOperator.' ', $this->where);
        }

        if ($this->groupBy) {
            $query .= ' GROUP BY '.implode(', ', $this->groupBy);
        }

        if (!$this->orderBy && !$this->orderByResetted) {
            $table = $this->getTable();
            $this->orderBy($this->getTableAlias().'.'.$table->getSortFieldName(), $table->getSortOrderName());
        }

        if ($this->orderBy) {
            $query .= ' ORDER BY '.implode(', ', $this->orderBy);
        }

        if ($this->limit) {
            $query .= ' LIMIT '.$this->limit;
        }

        return $query;
    }

    /**
     * @return array<string, int|string>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return rex_yform_manager_collection<T>
     */
    public function getIterator(): rex_yform_manager_collection
    {
        return $this->find();
    }

    /**
     * @return rex_yform_manager_collection<T>
     */
    public function find(): rex_yform_manager_collection
    {
        /** @var rex_yform_manager_collection<T> */
        return rex_yform_manager_dataset::queryCollection($this->getQuery(), $this->getParams(), $this->table);
    }

    /**
     * @return rex_yform_manager_collection<T>
     */
    public function paginate(rex_pager $pager): rex_yform_manager_collection
    {
        $pager->setRowCount($this->count());
        $this->limit($pager->getCursor(), $pager->getRowsPerPage());

        return $this->find();
    }

    /**
     * @param list<int> $ids
     *
     * @return rex_yform_manager_collection<T>
     */
    public function findIds(array $ids): rex_yform_manager_collection
    {
        return $this->where($this->getTableAlias().'.id', $ids)->find();
    }

    /**
     * @return null|T
     */
    public function findOne(): ?rex_yform_manager_dataset
    {
        $this->limit(1);

        /** @var null|T */
        return rex_yform_manager_dataset::queryOne($this->getQuery(), $this->getParams(), $this->table);
    }

    /**
     * @return null|T
     */
    public function findId(int $id): ?rex_yform_manager_dataset
    {
        return $this->where($this->getTableAlias().'.id', $id)->resetOrderBy()->findOne();
    }

    /**
     * @return scalar|null
     */
    public function findValue(string $column)
    {
        return $this->findValueRaw($this->quoteIdentifier($column));
    }

    /**
     * @return scalar|null
     */
    public function findValueRaw(string $expression)
    {
        $query = clone $this;
        $query
            ->resetSelect()
            ->selectRaw($expression, 'value')
            ->limit(1);

        $sql = rex_sql::factory();
        $sql->setQuery($query->getQuery(), $query->getParams());

        return $sql->getRows() ? $sql->getValue('value') : null;
    }

    /**
     * @return array<string|int, scalar|null>
     * @phpstan-return ($keyColumn is null ? list<scalar|null> : array<string|int, scalar|null>)
     */
    public function findValues(string $column, ?string $keyColumn = null): array
    {
        return $this->findValuesRaw($this->quoteIdentifier($column), $keyColumn);
    }

    /**
     * @return array<string|int, scalar|null>
     * @phpstan-return ($keyColumn is null ? list<scalar|null> : array<string|int, scalar|null>)
     */
    public function findValuesRaw(string $expression, ?string $keyColumn = null): array
    {
        $query = clone $this;
        $query
            ->resetSelect()
            ->selectRaw($expression, 'value');

        if ($keyColumn) {
            $query->select($keyColumn);
        }

        $sql = rex_sql::factory();
        $array = $sql->getArray($query->getQuery(), $query->getParams());

        return array_column($array, 'value', $keyColumn);
    }

    public function count(): int
    {
        $query = clone $this;
        $query
            ->resetSelect()
            ->selectRaw('COUNT(*)', 'count')
            ->resetOrderBy();

        $sql = rex_sql::factory();
        $sql->setQuery($query->getQuery(), $query->getParams());

        return (int) $sql->getValue('count');
    }

    public function exists(): bool
    {
        $query = clone $this;
        $query
            ->resetSelect()
            ->selectRaw('1')
            ->resetOrderBy()
            ->limit(1);

        $sql = rex_sql::factory();
        $sql->setQuery($query->getQuery(), $query->getParams());

        return $sql->getRows() > 0;
    }

    public function quoteIdentifier(string $identifier): string
    {
        $identifier = explode('.', $identifier, 2);
        foreach ($identifier as &$part) {
            if ('*' !== $part) {
                $part = '`'.str_replace('`', '``', $part).'`';
            }
        }

        return implode('.', $identifier);
    }

    /**
     * @param mixed $value
     * @return int|string
     */
    private function normalizeValue($value)
    {
        if (is_bool($value)) {
            return (int) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(rex_sql::FORMAT_DATETIME);
        }

        if (is_object($value)) {
            if (!method_exists($value, '__toString')) {
                throw new InvalidArgumentException('Objects without __toString() method are not supported');
            }

            return (string) $value;
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    private function addParam($value): string
    {
        $param = 'p'.$this->paramCounter++;
        $this->params[$param] = $this->normalizeValue($value);

        return ':'.$param;
    }

    /**
     * @param array<string, mixed> $where
     * @param 'AND'|'OR' $operator
     */
    private function buildNestedWhere(array $where, string $operator = 'AND'): string
    {
        $nextOperator = 'AND' === $operator ? 'OR' : 'AND';

        foreach ($where as $key => &$value) {
            if (is_array($value)) {
                $value = $this->buildNestedWhere($value, $nextOperator);
                continue;
            }

            $value = sprintf('%s = %s', $this->quoteIdentifier($key), $this->addParam($value));
        }

        return implode(' '.$operator.' ', $where);
    }
}
