<?php

/**
 * @template T of rex_yform_manager_dataset
 * @extends SplFixedArray<T>
 *
 * @method rex_yform_manager_dataset offsetGet($offset)
 * @method list<T> toArray()
 */
class rex_yform_manager_collection extends \SplFixedArray
{
    /** @var bool */
    private static $debug = false;

    /** @var string */
    private $table;

    /**
     * @param T[] $data
     */
    final public function __construct(string $table, array $data = [])
    {
        parent::__construct(count($data));

        $this->table = $table;
        $this->setData($data);
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
     * @param T[] $data
     * @return $this
     */
    public function setData(array $data): self
    {
        foreach ($data as $dataset) {
            if ($dataset->getTableName() !== $this->table) {
                throw new InvalidArgumentException(sprintf('$data has to be an array of rex_yform_manager_dataset objects of table "%s", found dataset of table "%s".', $this->table, $dataset->getTableName()));
            }
        }

        $this->setSize(count($data));

        $i = 0;
        foreach ($data as $dataset) {
            $this[$i++] = $dataset;
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    /**
     * @return null|T
     */
    public function first(): ?rex_yform_manager_dataset
    {
        return $this->count() ? $this[0] : null;
    }

    /**
     * @return null|T
     */
    public function last(): ?rex_yform_manager_dataset
    {
        return $this->count() ? $this[$this->count() - 1] : null;
    }

    /**
     * @param callable(T):bool $callback
     * @return static<T>
     */
    public function filter(callable $callback): self
    {
        return new static($this->table, array_filter($this->toArray(), $callback));
    }

    /**
     * @return static<T>
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new static($this->table, array_slice($this->toArray(), $offset, $length));
    }

    /**
     * @return static<T>
     */
    public function shuffle(): self
    {
        $data = $this->toArray();
        shuffle($data);

        return new static($this->table, $data);
    }

    /**
     * @param callable(T,T):int $callback
     * @return static<T>
     */
    public function sort(callable $callback): self
    {
        $data = $this->toArray();
        usort($data, $callback);

        return new static($this->table, $data);
    }

    /**
     * @template TResult
     * @param callable(T):TResult $callback
     * @return list<TResult>
     */
    public function map(callable $callback): array
    {
        /** @var list<TResult> */
        return array_map($callback, $this->toArray());
    }

    /**
     * @return list<list<T>>
     */
    public function split(int $groups): array
    {
        $size = (int) floor($this->count() / $groups);
        $mod = $this->count() % $groups;

        $data = $this->toArray();
        $result = [];
        $offset = 0;

        for ($i = 0; $i < $groups; ++$i) {
            $groupSize = $i < $mod ? $size + 1 : $size;

            $result[] = array_slice($data, $offset, $groupSize);

            $offset += $size;
        }

        return $result;
    }

    /**
     * @return list<list<T>>
     */
    public function chunk(int $size): array
    {
        return array_chunk($this->toArray(), $size);
    }

    /**
     * @return T[]
     */
    public function toKeyIndex(string $key = 'id'): array
    {
        $array = [];
        foreach ($this as $dataset) {
            $array[$dataset->getValue($key)] = $dataset;
        }

        return $array;
    }

    public function toKeyValue(string $key, string $value): array
    {
        $array = [];
        foreach ($this as $dataset) {
            $array[$dataset->getValue($key)] = $dataset->getValue($value);
        }

        return $array;
    }

    /**
     * @param string|string[] $keys
     */
    public function groupBy($keys, ?string $value = null): array
    {
        if (is_string($keys)) {
            $keys = [$keys];
        } else {
            $keys = array_reverse($keys);
        }

        $setValue = static function (&$array, array $keys, rex_yform_manager_dataset $dataset) use (&$setValue, $value) {
            if (!$keys) {
                $array[] = $value ? $dataset->getValue($value) : $dataset;
                return;
            }

            $key = array_pop($keys);
            $value = $dataset->getValue($key);
            if (!isset($array[$value])) {
                $array[$value] = [];
            }

            $setValue($array[$value], $keys, $dataset);
        };

        $array = [];
        foreach ($this as $dataset) {
            $setValue($array, $keys, $dataset);
        }

        return $array;
    }

    /**
     * @param string|Closure $value     Field name or callback
     * @param string         $separator Separator between elements
     * @param null|string    $and       Optional separator between last two elements
     */
    public function implode($value, string $separator, ?string $and = null): string
    {
        if (!$value instanceof Closure) {
            $value = static function (rex_yform_manager_dataset $dataset) use ($value) {
                return $dataset->getValue($value);
            };
        }

        $data = $this->map($value);

        if (null === $and || $this->count() < 2) {
            return implode($separator, $data);
        }

        $last = array_pop($data);

        return implode($separator, $data) . $and . $last;
    }

    /**
     * @return list<int>
     */
    public function getIds(): array
    {
        return $this->getValues('id');
    }

    /**
     * @return list<mixed>
     */
    public function getValues(string $key): array
    {
        $values = [];
        foreach ($this as $dataset) {
            if ($dataset->hasValue($key)) {
                $values[] = $dataset->getValue($key);
            }
        }

        return $values;
    }

    public function isValueUnique(string $key): bool
    {
        $uniqueValue = null;
        $hasNull = false;
        foreach ($this as $dataset) {
            if (!$dataset->hasValue($key)) {
                return false;
            }
            $value = $dataset->getValue($key);
            if (null === $value) {
                $hasNull = true;
            } elseif (null === $uniqueValue) {
                if ($hasNull) {
                    return false;
                }
                $uniqueValue = $value;
            } elseif ($uniqueValue !== $value) {
                return false;
            }
        }

        return !$this->isEmpty();
    }

    public function getUniqueValue(string $key)
    {
        return $this->isValueUnique($key) ? $this[0]->getValue($key) : null;
    }

    /**
     * @return $this
     */
    public function setValue(string $key, $value): self
    {
        foreach ($this as $dataset) {
            $dataset->setValue($key, $value);
        }

        return $this;
    }

    /**
     * @return self A new collection containing all datasets related to this collection by $key relation
     */
    public function populateRelation(string $key)
    {
        $relation = $this->getTable()->getRelation($key);

        if (!$relation) {
            throw new InvalidArgumentException(sprintf('Field "%s" in table "%s" is not a relation field.', $key, $this->getTableName()));
        }

        if ($this->isEmpty()) {
            return new self($relation['table']);
        }

        $query = rex_yform_manager_dataset::query($relation['table']);

        if (0 == $relation['type'] || 2 == $relation['type']) {
            $query->where('id', $this->getValues($key));

            return $query->find();
        }

        $relatedDatasets = [];
        foreach ($this as $dataset) {
            $relatedDatasets[$dataset->getId()] = [];
        }

        if (4 == $relation['type'] || 5 == $relation['type']) {
            $query->where($relation['field'], $this->getIds());

            $allRelatedDatasets = $query->find();

            foreach ($allRelatedDatasets as $dataset) {
                $relatedDatasets[$dataset->getValue($relation['field'])][] = $dataset;
            }
        } elseif (empty($relation['relation_table'])) {
            $relatedIds = [];
            foreach ($this as $dataset) {
                $ids = array_filter(explode(',', $dataset->getValue($key)));

                if (empty($ids)) {
                    continue;
                }

                foreach ($ids as $id) {
                    $relatedIds[(int) $id][$dataset->getId()] = true;
                }
            }

            $query->where('id', array_keys($relatedIds));

            $allRelatedDatasets = $query->find();

            foreach ($allRelatedDatasets as $dataset) {
                foreach ($relatedIds[$dataset->getId()] as $id => $_) {
                    $relatedDatasets[$id][] = $dataset;
                }
            }
        } else {
            $columns = $this->getTable()->getRelationTableColumns($key);
            $query
                ->join($relation['relation_table'], null, $relation['relation_table'] . '.' . $columns['target'], $relation['table'] . '.id')
                ->where($relation['relation_table'] . '.' . $columns['source'], $this->getIds())
                ->groupBy($relation['table'] . '.id')
                ->selectRaw(sprintf('GROUP_CONCAT(%s SEPARATOR ",")', $query->quoteIdentifier($relation['relation_table'] . '.' . $columns['source'])), '__related_ids')
            ;

            $allRelatedDatasets = $query->find();

            foreach ($allRelatedDatasets as $dataset) {
                foreach (explode(',', $dataset->getValue('__related_ids')) as $relatedId) {
                    $relatedDatasets[$relatedId][] = $dataset;
                }
            }
        }

        foreach ($this as $dataset) {
            $dataset->setRelatedCollection($key, new self($relation['table'], $relatedDatasets[$dataset->getId()]));
        }

        return $allRelatedDatasets;
    }

    public function isValid(): bool
    {
        $valid = true;
        foreach ($this as $dataset) {
            if (!$dataset->isValid()) {
                $valid = false;
            }
        }

        return $valid;
    }

    public function save(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        foreach ($this as $dataset) {
            if (!$dataset->save()) {
                return false;
            }
        }

        return true;
    }

    public function delete(): bool
    {
        $success = true;

        foreach ($this as $dataset) {
            $success = $dataset->delete() && $success;
        }

        return $success;
    }

    public function getForm(): \Yakamara\YForm\YForm
    {
        $yform = new \Yakamara\YForm\YForm();
        $yform->setDebug(self::$debug);
        $yform->objparams['form_name'] = 'yform-manager-multi-edit';
        $yform->objparams['form_class'] .= ' yform-manager-multi-edit';

        $send = $yform->getFieldValue('send');

        $i = 0;
        $validations = [];
        $useValidations = [];
        foreach ($this->getTable()->getFields() as $field) {
            if ('action' == $field->getType()) {
                continue;
            }

            /** @var class-string<rex_yform_base_abstract> $class */
            $class = 'rex_yform_' . $field->getType() . '_' . $field->getTypeName();

            /** @var rex_yform_base_abstract $cl */
            $cl = new $class();
            $definitions = $cl->getDefinitions();

            $values = [];
            foreach ($definitions['values'] as $key => $_) {
                $values[] = $field->getElement($key);
            }

            $mode = null;
            if (isset($definitions['multi_edit'])) {
                $mode = $definitions['multi_edit'];
                if ($mode instanceof Closure) {
                    $mode = $mode($field);
                }
            }

            if (null !== $mode && !$mode) {
                continue;
            }

            if ('validate' == $field->getType()) {
                if ($name = $field->getElement('name')) {
                    $validations[$name][] = ['type' => $field->getTypeName(), 'values' => $values];
                }
                continue;
            }

            $useCheckbox = 'always' !== $mode;
            $enabled = true;
            if ($useCheckbox) {
                if ($send && !$yform->getFieldValue($i)) {
                    $enabled = false;
                }
                ++$i;
            }

            if ($enabled) {
                $useValidations[$field->getName()] = true;
            }
            $values['validation_disabled'] = !$enabled;

            $key = $field->getName();
            $default = 0;
            if (!$send || !$enabled) {
                if ($this->isValueUnique($key)) {
                    $yform->setFieldValue((string) $i, [], $this->getUniqueValue($key));
                    $default = 1;
                }
            }

            if ($useCheckbox) {
                $yform->setValueField('checkbox', [
                    'name' => $key . '_multi_edit',
                    'label' => rex_i18n::msg('yform_manager_multi_edit_field', $field->getLabel()),
                    'default' => $default,
                    'no_db' => true,
                    'attributes' => ['data-multi-edit-checkbox' => 'true'],
                    '__multi_edit_checkbox' => $key,
                ]);
            }

            $yform->setValueField($field->getTypeName(), $values);
            ++$i;
        }

        foreach ($validations as $key => $keyValidations) {
            if (!isset($useValidations[$key])) {
                continue;
            }
            foreach ($keyValidations as $validation) {
                $yform->setValidateField($validation['type'], $validation['values']);
            }
        }

        $yform->setObjectparams('main_table', $this->table);

        return $yform;
    }

    /**
     * @param null|callable(\Yakamara\YForm\YForm):void $afterFieldsExecuted
     */
    public function executeForm(\Yakamara\YForm\YForm $yform, ?callable $afterFieldsExecuted = null): string
    {
        $yform->executeFields();

        if ($afterFieldsExecuted) {
            call_user_func($afterFieldsExecuted, $yform);
        }

        if (1 == $yform->objparams['send'] && !$yform->objparams['warning_messages']) {
            $ignoreFields = [];
            /** @var rex_yform_value_abstract $field */
            foreach ($yform->objparams['values'] as $field) {
                $key = $field->getElement('__multi_edit_checkbox');
                if ($key && !$field->getValue()) {
                    $ignoreFields[$key] = true;
                }
            }
            foreach ($yform->objparams['value_pool']['sql'] as $key => $value) {
                if (!isset($ignoreFields[$key])) {
                    $this->setValue($key, $value);
                }
            }

            if (!$this->save()) {
                foreach ($this as $dataset) {
                    foreach ($dataset->getMessages() as $message) {
                        $yform->objparams['warning_messages'][] = rex_i18n::msg('yform_data') . ' ID ' . $dataset->getId() . ': ' . $message;
                    }
                }
            }
        }

        $form = $yform->executeActions();

        return $form;
    }
}
