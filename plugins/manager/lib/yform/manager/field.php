<?php

class rex_yform_manager_field implements ArrayAccess
{
    protected $values = array();
    protected $definitions = array();
    protected static $debug = false;
    protected static $types = array('value', 'validate', 'action');

    function __construct(array $values)
    {
        $class = 'rex_yform_' . $values['type_id'] . '_' . $values['type_name'];

        if (count($values) == 0 || !class_exists($class)) {
            throw new Exception(rex_i18n::msg('yform_field_not_found'));
        }

        $object = new $class;
        $this->definitions = $object->getDefinitions();
        if (isset($this->definitions['values'])) {
            $i = 'validate' === $values['type_id'] ? 2 : 1;
            foreach ($this->definitions['values'] as $key => $value) {
                if (isset($values['f' . $i]) && (!isset($values[$key]) || is_null($values[$key]) || '' === $values[$key])) {
                    $values[$key] = $values['f' . $i];
                }
                $i++;
            }
        }
        $this->values = $values;

    }

    public static function table()
    {
        return rex::getTablePrefix() . 'yform_field';
    }

    // value, validate, action
    public function getType()
    {
        $type_id =  $this->values['type_id'];
        if (!in_array($type_id, self::$types)) {
            return false;
        }
        return $type_id;
    }

    // rex_yform_select
    public function getTypeName()
    {
        if (!isset($this->values['type_name'])) {
            return '';
        }
        return $this->values['type_name'];
    }

    public function getName()
    {
        return $this->values['name'];
    }

    public function getLabel()
    {
        return rex_i18n::translate($this->values['label']);
    }

    public function getElement($k)
    {
        if (!isset($this->values[$k])) {
            return null;
        }
        return $this->values[$k];
    }

    public function isSearchable()
    {
        if (isset($this->definitions["search"]) && !$this->definitions["search"]){
            return false;
        }

        if (isset($this->values['search']) && $this->values['search'] == 1) {
            return true;
        }
        return false;
    }

    public function isHiddenInList()
    {
        if (isset($this->definitions["list_hidden"]) && $this->definitions["list_hidden"]) {
            return true;
        }

        if (isset($this->values['list_hidden']) && !$this->values['list_hidden']) {
            return false;
        }
        return true;
    }

    // deprecated
    // sobald die yform value klassen umgebaut worden sind.
    public function toArray()
    {
        return $this->values;

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
        return $this->getName();
    }

}
