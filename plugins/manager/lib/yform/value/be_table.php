<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */
class rex_yform_value_be_table extends rex_yform_value_abstract
{
    protected $fieldData = [];

    public function preValidateAction()
    {
        // bc service for Version < 1.1
        if ($this->getValue() != '' && json_decode($this->getValue()) == '') {
            $rows = explode(';', $this->getValue());
            foreach ($rows as $row_id => $row) {
                $rows[$row_id] = explode(',', $row);
            }
            $this->setValue(json_encode($rows));
        }

        if ($this->getParam('send') && isset($_POST['FORM'])) {
            // Cleanup Array
            $table_array = [];

            $id = $this->getName();

            $columns = preg_split("/(?<=[^\w\"]),|,(?=\{)|(?<=[A-Za-z]),(?=[^ ][\w,])|(?<=,\w),/", $this->getElement('columns'));
            if (count($columns) == 0) {
                return;
            }

            $form_data = rex_post('FORM', 'array');

            if (isset($form_data[$id . '.0'])) {
                $rowKeys = array_keys((array) $form_data[$id . '.0']);

                // Spalten durchgehen
                for ($c = 0; $c < count($columns); ++$c) {
                    foreach ($rowKeys as $r) {
                        $table_array[$r][$c] = (isset($form_data[$id . '.' . $c][$r])) ? $form_data[$id . '.' . $c][$r] : '';
                    }
                }
            }
            $this->setValue(json_encode(array_values($table_array)));
        }
    }

    public static function getColumnsByName($definition)
    {
        $valueFields = [];
        $validateFields = [];
        $_columns = preg_split ("/(?<=[^\w\"]),|,(?=\{)|(?<=[A-Za-z]),(?=[^ ][\w,])|(?<=,\w),/" , $definition);

        if (count($_columns)) {
            foreach ($_columns as $index => $col) {
                // Use ;; for separating choice columns instead of ,
                $values = explode('|', trim(trim(str_replace(';;', ',', rex_yform::unhtmlentities($col))), '|'));
                if (count($values) == 1) {
                    $values = ['text', 'text_'. $index, $values[0]];
                }

                $class = 'rex_yform_value_' . trim($values[0]);
                if(class_exists($class)) {
                    $name = $values[1];
                    $values[1] = '';

                    $valueFields[] = [
                        'field' => 'value',
                        'index' => $index,
                        'type' => $values[0],
                        'name' => $name,
                        'label' => $values[2],
                        'class' => $class,
                        'values' => $values
                    ];
                }
                elseif (class_exists('rex_yform_validate_' . trim($values[1]))) {
                    $validateFields[] = [
                        'field' => 'validate',
                        'index' => $index,
                        'type' => trim($values[1]),
                        'name' => $values[2],
                        'msg' => $values[3],
                        'class' => 'rex_yform_validate_' . trim($values[1]),
                        'values' => $values
                    ];
                }
            }
        }
        return array_merge($valueFields, $validateFields);
    }

    public function enterObject()
    {
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

        if (count($_columns) == 0) {
            return;
        }

        $data = (array) json_decode($this->getValue(), true);
        $objs = [];
        $columnIndex = [];

        $this->fieldData = $data;

        $yfparams = \rex_yform::factory()->objparams;
        $yfparams['this'] = \rex_yform::factory();

        /* TODO
         * error class von validierung ans Eingabefeld übergeben
         */

        foreach ($_columns as $name => $col) {
            $field = new $col['class']();

            if ($col['field'] == 'value') {
                $field->loadParams($yfparams, $col['values']);
                $field->setName($this->getName());
                $field->init();
                $field->setLabel('');

                $columnIndex[$col['name']] = $col['index'];
                $columns[] = ['label' => $col['label'], 'field' => $field];

                foreach ($data as $rowCount => $row) {
                    $obj = clone $field;
                    $rdata = array_values($data[$rowCount]);
                    $obj->setName($col['name'] . $rowCount . $col['index']);
                    $obj->setValue($rdata[$col['index']]);
                    $objs[]= $obj;
                }
            }
            elseif ($col['field'] == 'validate') {
                $field->setObjects($objs);

                foreach($data as $rowCount => $row) {
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

        $this->params['form_output'][$this->getId()] = $this->parse('value.be_table.tpl.php', compact('columns', 'data'));

        if ($this->getParam('send')) {
            $this->setValue(json_encode($this->fieldData));

            if ($this->getElement('no_db') != 1) {
                $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
            }
            $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        }
    }

    public function setFieldData($index, $key, $value) {
        $this->fieldData[$index][$key] = $value;
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'be_table',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'columns' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_be_table_columns')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
            ],
            'description' => rex_i18n::msg('yform_values_be_table_description'),
            'formbuilder' => false,
            'db_type' => ['text'],
        ];
    }
}
