<?php

/**
 * @method rex_yform_manager_dataset offsetGet($offset)
 * @method rex_yform_manager_dataset current()
 * @method rex_yform_manager_dataset[] toArray()
 */
class rex_yform_manager_collection extends \SplFixedArray
{
    private static $debug = false;

    private $table;

    /**
     * @param string                      $table
     * @param rex_yform_manager_dataset[] $data
     */
    public function __construct($table, array $data = [])
    {
        parent::__construct(count($data));

        $this->table = $table;
        $this->setData($data);
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->table;
    }

    /**
     * @return rex_yform_manager_table
     */
    public function getTable()
    {
        return rex_yform_manager_table::get($this->table);
    }

    /**
     * @param rex_yform_manager_dataset[] $data
     *
     * @return $this
     */
    public function setData(array $data)
    {
        foreach ($data as $dataset) {
            if (!$dataset instanceof rex_yform_manager_dataset) {
                throw new InvalidArgumentException(sprintf(
                    '$data has to be an array of rex_yform_manager_dataset objects, found "%s" array element.',
                    is_object($dataset) ? get_class($dataset) : gettype($dataset)
                ));
            }
            if ($dataset->getTableName() !== $this->table) {
                throw new InvalidArgumentException(sprintf(
                    '$data has to be an array of rex_yform_manager_dataset objects of table "%s", found dataset of table "%s".',
                    $this->table,
                    $dataset->getTableName()
                ));
            }
        }

        $this->setSize(count($data));

        $i = 0;
        foreach ($data as $dataset) {
            $this[$i++] = $dataset;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return 0 === $this->count();
    }

    /**
     * @param string $key
     *
     * @return rex_yform_manager_dataset[]
     */
    public function toKeyIndex($key = 'id')
    {
        $array = [];
        foreach ($this as $dataset) {
            $array[$dataset->getValue($key)] = $dataset;
        }

        return $array;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return array
     */
    public function toKeyValue($key, $value)
    {
        $array = [];
        foreach ($this as $dataset) {
            $array[$dataset->getValue($key)] = $dataset->getValue($value);
        }

        return $array;
    }

    /**
     * @param string|string[] $keys
     * @param null|string     $value
     *
     * @return array
     */
    public function groupBy($keys, $value = null)
    {
        if (is_string($keys)) {
            $keys = [$keys];
        } else {
            $keys = array_reverse($keys);
        }

        $setValue = function (&$array, array $keys, rex_yform_manager_dataset $dataset) use (&$setValue, $value) {
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
     * @return int[]
     */
    public function getIds()
    {
        return $this->getValues('id');
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getValues($key)
    {
        $values = [];
        foreach ($this as $dataset) {
            if ($dataset->hasValue($key)) {
                $values[] = $dataset->getValue($key);
            }
        }

        return $values;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isValueUnique($key)
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

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getUniqueValue($key)
    {
        return $this->isValueUnique($key) ? $this[0]->getValue($key) : null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setValue($key, $value)
    {
        foreach ($this as $dataset) {
            $dataset->setValue($key, $value);
        }

        return $this;
    }

    public function populateRelation($key)
    {
        $relation = $this->getTable()->getRelation($key);

        if (!$relation) {
            throw new InvalidArgumentException(sprintf('Field "%s" in table "%s" is not a relation field.', $key, $this->getTableName()));
        }

        if ($this->isEmpty()) {
            return;
        }

        $query = rex_yform_manager_dataset::query($relation['table']);

        if (0 == $relation['type'] || 2 == $relation['type']) {
            $query->where('id', $this->getValues($key));
            $query->find();

            return;
        }

        $relatedDatasets = [];
        foreach ($this as $dataset) {
            $relatedDatasets[$dataset->getId()] = [];
        }

        if (4 == $relation['type']) {
            $query->where($relation['field'], $this->getIds());

            foreach ($query->find() as $dataset) {
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
                    $relatedIds[$id][$dataset->getId()] = true;
                }
            }

            $query->where('id', array_keys($relatedIds));

            foreach ($query->find() as $dataset) {
                foreach ($relatedIds[$dataset->getId()] as $id => $_) {
                    $relatedDatasets[$id][] = $dataset;
                }
            }
        } else {
            $columns = $this->getTable()->getRelationTableColumns($key);
            $query
                ->join($relation['relation_table'], null, $relation['relation_table'].'.'.$columns['target'], $relation['table'].'.id')
                ->where($relation['relation_table'].'.'.$columns['source'], $this->getIds())
                ->groupBy($relation['table'].'.id')
                ->selectRaw(sprintf('GROUP_CONCAT(%s SEPARATOR ",")', $query->quoteIdentifier($relation['relation_table'].'.'.$columns['source'])), '__related_ids')
            ;

            foreach ($query->find() as $dataset) {
                foreach (explode(',', $dataset->getValue('__related_ids')) as $relatedId) {
                    $relatedDatasets[$relatedId][] = $dataset;
                }
            }
        }

        foreach ($this as $dataset) {
            $dataset->setRelatedCollection($key, new self($relation['table'], $relatedDatasets[$dataset->getId()]));
        }
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $valid = true;
        foreach ($this as $dataset) {
            if (!$dataset->isValid()) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * @return bool
     */
    public function save()
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

    /**
     * @return bool
     */
    public function delete()
    {
        $success = true;

        foreach ($this as $dataset) {
            $success = $dataset->delete() && $success;
        }

        return $success;
    }

    /**
     * @return rex_yform
     */
    public function getForm()
    {
        $yform = new rex_yform();
        $yform->setDebug(self::$debug);
        $yform->objparams['form_class'] .= ' yform-manager-multi-edit';

        $send = $yform->getFieldValue('send', '', 'send');

        $i = 0;
        $validations = [];
        $useValidations = [];
        foreach ($this->getTable()->getFields() as $field) {
            if ($field->getType() == 'action') {
                continue;
            }

            $class = 'rex_yform_'.$field->getType().'_'.$field->getTypeName();

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

            if ($field->getType() == 'validate') {
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
                    $yform->setFieldValue($i, $this->getUniqueValue($key));
                    $default = 1;
                }
            }

            if ($useCheckbox) {
                $yform->setValueField('checkbox', [
                    $key.'_multi_edit',
                    rex_i18n::msg('yform_manager_multi_edit_field', $field->getLabel()),
                    '0,1',
                    $default,
                    'no_db',
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

    public function executeForm(rex_yform $yform, callable $afterFieldsExecuted = null)
    {
        $yform->executeFields();

        if ($afterFieldsExecuted) {
            call_user_func($afterFieldsExecuted, $yform);
        }

        if ($yform->objparams['send'] == 1 && !$yform->objparams['warning_messages']) {
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
                        $yform->objparams['warning_messages'][] = rex_i18n::msg('yform_data').' ID '.$dataset->getId().': '.$message;
                    }
                }
            }
        }

        $form = $yform->executeActions();

        return $form;
    }
}
