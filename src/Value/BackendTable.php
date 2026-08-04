<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\FieldRegistry;
use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\YForm;

#[AsValue('be_table')]
class BackendTable extends AbstractValue
{
    protected $fieldData = [];

    public static function getColumnsByName($definition)
    {
        $valueFields = [];
        $validateFields = [];
        $_columns = preg_split('/(?<=[^\\w"]),|,(?=\\{)|(?<=[A-Za-z]),(?=[^ ][\\w,])|(?<=,\\w),/', $definition);

        if (count($_columns)) {
            foreach ($_columns as $index => $col) {
                // Use ;; for separating choice columns instead of ,
                $values = explode('|', trim(trim(str_replace(';;', ',', YForm::unhtmlentities($col))), '|'));
                if (1 == count($values)) {
                    $values = ['text', 'text_' . $index, $values[0]];
                }

                $class = FieldRegistry::getClass('value', trim($values[0]));
                if (class_exists($class)) {
                    $name = $values[1];
                    $values[1] = '';

                    $valueFields[] = [
                        'field' => 'value',
                        'index' => $index,
                        'type' => $values[0],
                        'name' => $name,
                        'label' => $values[2],
                        'class' => $class,
                        'values' => $values,
                    ];
                } elseif (null !== FieldRegistry::getClass('validate', trim($values[1]))) {
                    $validateFields[] = [
                        'field' => 'validate',
                        'index' => $index,
                        'type' => trim($values[1]),
                        'name' => $values[2],
                        'msg' => $values[3],
                        'class' => FieldRegistry::getClass('validate', trim($values[1])),
                        'values' => $values,
                    ];
                }
            }
        }
        return array_merge($valueFields, $validateFields);
    }

    public function enterObject()
    {
        if (is_array($this->getValue())) {
            $this->setValue(json_encode(array_values($this->getValue())));
        }
        if (!$this->getValue()) {
            $this->setValue(json_encode([]));
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput()) {
            return;
        }

        $_columns = self::getColumnsByName($this->getElement('columns'));

        if (0 == count($_columns)) {
            return;
        }

        $data = (array) json_decode($this->getValue(), true);
        $objs = [];
        $columnIndex = [];
        $columns = [];

        $this->fieldData = $data;

        $yfparams = YForm::factory()->objparams;
        $yfparams['this'] = YForm::factory();

        /* TODO
         * error class von validierung ans Eingabefeld übergeben
         */

        foreach ($_columns as $name => $col) {
            $field = new $col['class']();

            if ('value' == $col['field']) {
                $field->loadParams($yfparams, $col['values']);
                $field->setName($this->getFieldName() . '][' . $this->getId() . ']');
                $field->init();
                $field->setLabel('');

                $columnIndex[$col['name']] = $col['index'];
                $columns[] = ['label' => $col['label'], 'field' => $field];

                foreach ($data as $rowCount => $row) {
                    $obj = clone $field;
                    $rdata = array_values($data[$rowCount]);
                    $obj->setName($col['name'] . $rowCount . $col['index']);
                    $obj->setValue($rdata[$col['index']]);
                    $objs[] = $obj;
                }
            } elseif ('validate' == $col['field']) {
                $field->setObjects($objs);

                foreach ($data as $rowCount => $row) {
                    $col['values'][2] = $col['name'] . $rowCount . $columnIndex[$col['name']]; // TODO: check in tpl

                    $field->loadParams($this->params, $col['values']);
                    $field->init();
                    $field->enterObject();
                }
            }
        }

        if (!is_array($data)) {
            $data = [];
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['be_table.view', 'view'], compact('columns', 'data'));
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('be_table', compact('columns', 'data'));
            }
        }

        if ($this->getParam('send')) {
            $this->setValue(json_encode($this->fieldData));

            if (1 != $this->getElement('no_db')) {
                $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
            }
            $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        }
    }

    public function setFieldData($index, $key, $value)
    {
        $this->fieldData[$index][$key] = $value;
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'be_table',
            'values' => [
                'name' => ['type' => 'name', 'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_label')],
                'columns' => ['type' => 'text', 'label' => I18n::msg('yform_values_be_table_columns')],
                'notice' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_notice')],
                'no_db' => ['type' => 'no_db', 'label' => I18n::msg('yform_values_defaults_table'), 'default' => 0],
            ],
            'description' => I18n::msg('yform_values_be_table_description'),
            'formbuilder' => false,
            'db_type' => ['text'],
        ];
    }
}
