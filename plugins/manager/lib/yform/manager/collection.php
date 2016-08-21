<?php

/**
 * @method rex_yform_manager_dataset offsetGet($offset)
 * @method rex_yform_manager_dataset current()
 * @method rex_yform_manager_dataset[] toArray()
 */
class rex_yform_manager_collection extends \SplFixedArray
{
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
     * @return int[]
     */
    public function getIds()
    {
        return $this->getColumnValues('id');
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getColumnValues($key)
    {
        $values = [];
        foreach ($this as $dataset) {
            $values[] = $dataset->getValue($key);
        }

        return $values;
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
}
