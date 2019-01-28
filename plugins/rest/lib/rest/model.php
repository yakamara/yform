<?php

class rex_yform_rest_model
{
    public $config = [];

    public static $requestMethods = ['get', 'post', 'delete'];

    public function __construct($config)
    {
        // TODO:
        // Prüfen ob alles gesetzt ist.
        $this->config = $config;
    }

    public function hasAuth()
    {
        if (isset($this->config['auth'])) {
            if (is_callable($this->config['auth'])) {
                return call_user_func($this->config['auth']);
            }
            return $this->config['auth'];
        }
        return true;
    }

    public function handleRequest($paths, $get)
    {
        $type = array_shift($paths);

        if (!isset($this->config['table'])) {
            \rex_yform_rest::sendError(400, 'table-not-available');
        }

        $requestMethod = $this->getRequestMethod();
        if (in_array($requestMethod, self::$requestMethods) && !isset($this->config[$requestMethod])) {
            \rex_yform_rest::sendError(400, 'request-method-not-available');
        }

        /* @var \rex_yform_manager_table $table */
        $table = $this->config['table'];

        /** @var rex_yform_manager_query $query */
        $query = $this->config['query'];

        switch ($requestMethod) {
            case 'get':

                $fields = $this->getFieldsFromModelType('get');

                if (count($paths) > 0) {

                    // TODO:

                    // instance
                    /*
                    $id = array_shift($paths);
                    $query
                        ->where('id', $id);
                    $instance = $query->findOne();

                    if (!$instance) {
                        \rex_yform_rest::sendError(400, 'no-dataset-found', ['id' => $id, 'table' => $table->getTableName()]);
                    }

                    if (count($paths) > 0) {
                        // single property could be array

                        $field = array_pop($paths);
                        if (array_key_exists($field, $fields)) {

                            // $this->getFieldsValue(\rex_yform_manager_dataset $instance, $fields, $deep = 0)
                            $data = $this->getFieldsValue($instance, $fields);
                            // $data = $instance->getValue($field);
                        }

                        $content = [$data];

                        \rex_yform_rest::sendContent(200, $content);
                    }

                    // all fields of instance

                    $data = $this->getFieldsValue($instance, $fields);

                    \rex_yform_rest::sendContent(200, $data);
                    */
                } else {

                    // instances

                    // dump($query->count());

                    $query = $this->getFilterQuery($query, $fields, $get);

                    // page, per_page

                    $per_page = (isset($get['per_page'])) ? (int) $get['per_page'] : (int) $table->getListAmount();
                    $per_page = ($per_page < 0) ? $per_page = $table->getListAmount() : $per_page;

                    $page = (isset($get['page'])) ? (int) $get['page'] : 1;
                    $page = ($page < 0) ? 1 : $page;

                    $query->limit(($page - 1) * $per_page, $per_page);

                    if ($get['order'] && is_array($get['order'])) {

                        foreach ($get['order'] as $orderName => $orderValue) {
                            if (array_key_exists($orderName, $fields)) {
                                if ($orderValue != 'desc') {
                                    $orderValue = 'asc';
                                }
                                $query->orderBy($orderName, ($orderValue != 'desc') ? 'asc' : 'desc');
                            }
                        }
                        $query->orderBy($table->getSortFieldName(), $table->getSortOrderName());

                    }

                    $instances = $query->find();

                    $data = [];
                    foreach ($instances as $instance) {
                        $data[] = [
                            'id' => $instance->getId(),
                            'type' => $this->getTypeFromInstance($instance),
                            'attributes' => $this->getInstanceAttributes($instance, $fields),
                            'relationships' => $this->getInstanceRelationships($instance, $fields),
                            'links' => [
                                'self' => 'TODO'
                            ]
                        ];
                    }

                    $collection = [
                        'links' => [
                            'self' => 'TODO'
                        ],
                        'meta' => [
                            'totalItems' => 'TODO',
                            'itemsPerPage' => $per_page,
                            'currentPage' => $page
                        ],
                        'data' => $data
                    ];

                    \rex_yform_rest::sendContent(200, $collection);
                }

                break;

            case 'post':

                $errors = [];
                $fields = $this->getFieldsFromModelType('post');

                $in = json_decode(file_get_contents('php://input'), true);

                $dataset = null;
                if (isset($in['id'])) {
                    $dataset = $table->getDataset($in['id']);
                    $OKStatus = 200; // update
                }

                if (!$dataset) {
                    if (isset($in['id'])) {
                        $dataset = $table->getRawDataset($in['id']);
                    }
                    if (!$dataset) {
                        $dataset = $table->createDataset();
                    }
                    $OKStatus = 201; // created
                }

                foreach ($in as $inKey => $inValue) {
                    if (array_key_exists($inKey, $fields)) {
                        $dataset->setValue($inKey, $inValue);
                    }
                }

                if ($dataset->save()) {
                    \rex_yform_rest::sendContent($OKStatus, ['id' => $dataset->getId()]);
                } else {
                    foreach ($dataset->getMessages() as $message_key => $message) {
                        // TODO: Info wenn Meldung leer.. siehe leere Meldungen bei YForm
                        $errors[] = \rex_i18n::translate($message);
                    }
                    \rex_yform_rest::sendError(400, 'errors-set', $errors);
                }
                break;

            case 'delete':

                $fields = $this->getFieldsFromModelType('delete');

                $queryClone = clone $query;
                $query = $this->getFilterQuery($query, $fields, $get);

                if ($queryClone === $query && isset($get['filter'])) {
                    \rex_yform_rest::sendError(404, 'no-available-filter-set');
                } elseif ($queryClone != $query) {
                    // filter set -> true
                } elseif (count($paths) == 0) {
                    \rex_yform_rest::sendError(404, 'no-id-set');
                } else {
                    $id = $paths[0];
                    $query->where('id', $id);
                }

                $data = $query->find();

                $content = [];
                $content['all'] = count($data);
                $content['deleted'] = 0;
                $content['failed'] = 0;

                foreach ($data as $i_data) {
                    $date = [];
                    $date['id'] = $i_data->getId();
                    if ($i_data->delete()) {
                        ++$content['deleted'];
                    } else {
                        ++$content['failes'];
                    }
                    $content['dataset'][] = $date;
                }

                \rex_yform_rest::sendContent(200, $content);

                break;

            default:
                $availableMethods = [];
                foreach (self::$requestMethods as $method) {
                    if (isset($this->config[$method])) {
                        $availableMethods[] = strtoupper($method);
                    }
                }
                \rex_yform_rest::sendError(404, 'no-request-method-found', ['please only use: ' . implode(',', $availableMethods)]);
        }
    }

    public function getFieldsFromModelType($type, $table = null)
    {
        /* @var $table \rex_yform_manager_table */
        if (!$table) {
            $table = $this->config['table'];
        }

        if (!is_object($table)) {
            throw  new rex_api_exception('Problem with Config: A Table/Class does not exists ');
        }
        $availableFields = $table->getValueFields();
        $returnFields = ['id' => new \rex_yform_manager_field([
            'name' => 'id',
            'type_id' => 'value',
            'type_name' => 'integer',
        ])];
        $fields = (!isset($this->config[$type]['fields'])) ? [] : $this->config[$type]['fields'];

        foreach ($availableFields as $key => $availableField) {
            if ($availableField->getDatabaseFieldType() != 'none') {
                if (count($fields) == 0 || in_array($key, $fields)) {
                    $returnFields[$key] = $availableField;
                }
            }
        }
        return $returnFields;
    }

    public function getFilterQuery($query, $fields, $get)
    {
        if (isset($get['filter']) && is_array($get['filter'])) {
            foreach ($get['filter'] as $filterKey => $filterValue) {
                foreach ($fields as $fieldName => $field) {
                    if ($fieldName == $filterKey) {
                        if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getSearchFilter')) {
                            try {
                                $rawQuery = $field->object->getSearchFilter([
                                    'value' => $filterValue,
                                    'field' => $field,
                                ]);
                            } catch (Error $e) {
                                \rex_yform_rest::sendError(400, 'field-class-not-found', ['field' => $fieldName, 'table' => $table->getTableName()]);
                                exit;
                            }
                            $query->whereRaw('(' . $rawQuery . ')');
                        } else {
                            $query->where($filterKey, $filterValue);
                        }
                    }
                }
            }
        }
        return $query;
    }

    public function getInstanceAttributes(\rex_yform_manager_dataset $instance, $fields)
    {
        $data = [];
        foreach ($fields as $fieldName => $field) {
            if ($field->getTypeName() != 'be_manager_relation') {
                $data[$fieldName] = $instance->getValue($field->getName());
            }
        }
        return $data;

    }

    public function getInstanceRelationships(\rex_yform_manager_dataset $instance, $fields)
    {
        $return = [];
        foreach ($fields as $field) {
            if ($field->getTypeName() == 'be_manager_relation') {
                $collection = $instance->getRelatedCollection($field->getName());
                $data = [];
                foreach ($collection as $entry) {
                    $data[] = [
                        'type' => $this->getTypeFromInstance($entry),
                        'id' => $entry->getId()
                    ];
                }
                if (count($data) > 0) {
                    $return[$field->getName()] = [
                        'data' => $data
                    ];

                }
            }
        }

        return $return;

    }

    public function getRequestMethod()
    {
        // TODO: implement: X-HTTP-Method-Override: PUT
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function getTypeFromInstance($instance)
    {
        $type = get_class($instance);
        if ($type == "rex_yform_manager_dataset") {
            $type = 'not-defined';
        }
        return $type;
    }

}
