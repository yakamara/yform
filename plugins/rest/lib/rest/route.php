<?php

class rex_yform_rest_route
{
    public $config = [];

    public static $requestMethods = ['get', 'post', 'delete'];

    public function __construct($config)
    {
        $this->config = $config;
        $this->config['table'] = $config['type']::table();
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

    public function getPath()
    {
        return $this->config['path'];
    }

    public function handleRequest($paths, $get)
    {
        // dump($paths);exit;
        // $type = array_shift($paths);

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

                    /*
                     * Beispiele:
                    /77
                    /77/name
                    /77/autos
                    /77/quatsch
                    /77/autos/32
                    /77/autos/32/name
                    /77/autos/32/prio
                    /77/autos/32/years
                    /77/autos/32/years/40
                    /77/autos/32/years/40/name
                    */

                    /* @var rex_yform_manager_dataset $instance */
                    $instance = null;
                    /* @var rex_yform_manager_collection $instance */
                    $instances = null;
                    $attribute = null;

                    foreach ($paths as $path) {

                        if ($instances) {
                            $id = $path;
                            foreach ($instances as $i_instance) {
                                if ($i_instance->getId() == $id) {
                                    $instance = $i_instance;
                                }
                            }
                            if (!$instance) {
                                \rex_yform_rest::sendError(400, 'dataset-not-found', ['paths' => $paths, 'table' => $instances->getTable()->getTableName()]);

                            }
                            $attribute = null;
                            $instances = null;

                        } elseif (!$instance) {
                            $id = $path;
                            if (!$instance) {
                                $id_column = 'id';
                                if ($query->getTableAlias() != '') {
                                    $id_column = $query->getTableAlias().'.id';
                                }

                                $query
                                    ->where($id_column, $id);
                                $instance = $query->findOne();

                                if (!$instance) {
                                    \rex_yform_rest::sendError(400, 'dataset-not-found', ['paths' => $paths, 'table' => $query->getTable()->getTableName()]);
                                }

                            }
                            $attribute = null;

                        } else {

                            $attribute = $path;

                            if (!array_key_exists($attribute, $fields)) {
                                \rex_yform_rest::sendError(400, 'attribute-not-found', ['paths' => $paths, 'table' => $table->getTableName()]);
                            }

                            if ($fields[$attribute]->getTypeName() == 'be_manager_relation') {
                                $instances = $instance->getRelatedCollection($attribute);
                                if (count($instances) > 0) {
                                    $instance = $instances->current();
                                }
                                $fields = self::getFieldsFromModelType('get', $instances->getTable(), $this->getTypeFromInstance($instance));
                                $instance = null;

                            }

                        }

                    }

                    if ($instances) {

                        $data = [];
                        foreach ($instances as $instance) {
                            $data[] = [
                                'id' => $instance->getId(),
                                'type' => $this->getTypeFromInstance($instance),
                                'attributes' => $this->getInstanceAttributes($instance, $fields),
                                'relationships' => $this->getInstanceRelationships($instance, $fields, $paths),
                                'links' => [
                                    'self' => \rex_yform_rest::getLinkByPath($this, [], $paths+[$instance->getId()])
                                ]
                            ];
                        }

                    } elseif ($instance) {

                        if ($attribute) {
                            $data = $instance->getValue($attribute);

                        } else {
                            $data = [
                                'id' => $instance->getId(),
                                'type' => $this->getTypeFromInstance($instance),
                                'attributes' => $this->getInstanceAttributes($instance, $fields),
                                'relationships' => $this->getInstanceRelationships($instance, $fields),
                                'links' => [
                                    'self' => \rex_yform_rest::getLinkByPath($this, [], $paths)
                                ]
                            ];

                        }

                    }

                    \rex_yform_rest::sendContent(200, $data);

                } else {

                    // instances

                    $query = $this->getFilterQuery($query, $fields, $get);
                    $itemsAll = $query->count();

                    $per_page = (isset($get['per_page'])) ? (int) $get['per_page'] : (int) $table->getListAmount();
                    $per_page = ($per_page < 0) ? $per_page = $table->getListAmount() : $per_page;

                    $currentPage = (isset($get['page'])) ? (int) $get['page'] : 1;
                    $currentPage = ($currentPage < 0) ? 1 : $currentPage;

                    $query->limit(($currentPage - 1) * $per_page, $per_page);

                    $order = [];
                    if ($get['order'] && is_array($get['order'])) {
                        foreach ($get['order'] as $orderName => $orderValue) {
                            if (array_key_exists($orderName, $fields)) {
                                $orderValue = ($orderValue != 'desc') ? 'asc' : 'desc';
                                $order[$orderName] = $orderValue;
                                $query->orderBy($orderName, $orderValue);
                            }
                        }
                        $order[$table->getSortFieldName()] = $table->getSortOrderName();
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
                                'self' => \rex_yform_rest::getLinkByPath($this, [], [$instance->getId()])
                            ]
                        ];
                    }

                    $linkParams = [
                        'page' => $currentPage,
                        'per_page' => $per_page,
                        'order' => $order,
                    ];

                    if (isset($get['filter']) && is_array($get['filter'])) {
                        $linkParams['filter'] = $get['filter'];
                    }

                    $links = [];
                    $links['self'] = \rex_yform_rest::getLinkByPath($this, $linkParams);
                    $links['first'] = \rex_yform_rest::getLinkByPath($this, array_merge(
                        $linkParams, ['page' => 1]
                    ));
                    if (($currentPage - 1) > 0) {
                        $links['prev'] = \rex_yform_rest::getLinkByPath($this, array_merge(
                            $linkParams, ['page' => ($currentPage - 1)]
                        ));
                    }
                    if ( ($currentPage * $per_page) < $itemsAll) {
                        $links['next'] = \rex_yform_rest::getLinkByPath($this, array_merge(
                            $linkParams, ['page' => ($currentPage + 1)]
                        ));
                    }

                    $collection = [
                        'links' => $links,
                        'meta' => [
                            'totalItems' => (int) $itemsAll,
                            'currentItems' => count($instances),
                            'itemsPerPage' => $per_page,
                            'currentPage' => $currentPage
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

    public function getFieldsFromModelType($type, $table = null, $classType = null)
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

        if (!$classType) {
            $classType = $this->config['type'];
        }

        $fields = ($classType == '' || !isset($this->config[$type]['fields'][$classType])) ? ['id'] : $this->config[$type]['fields'][$classType];

        foreach ($availableFields as $key => $availableField) {
            if ($availableField->getDatabaseFieldType() != 'none') {
                if (count($fields) == 0 || in_array($key, $fields, true)) {
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

    public function getInstanceRelationships(\rex_yform_manager_dataset $instance, $fields, $paths = [])
    {
        $paths[] = $instance->getId();

        $return = [];
        foreach ($fields as $field) {
            if ($field->getTypeName() == 'be_manager_relation') {
                $collection = $instance->getRelatedCollection($field->getName());
                $data = [];
                foreach ($collection as $entry) {
                    $data[] = [
                        'type' => $this->getTypeFromInstance($entry),
                        'id' => $entry->getId(),
                        'links' => [
                            'self' => \rex_yform_rest::getLinkByPath($this, [], $paths + [$field->getName()] + [$entry->getId()])
                        ]
                    ];
                }
                if (count($data) > 0) {
                    $return[$field->getName()] = [
                        'data' => $data
                    ];

                }
                $return['links'] = [
                    'self' => \rex_yform_rest::getLinkByPath($this, [], $paths + [$field->getName()])
                ];
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
        if ($type == 'rex_yform_manager_dataset') {
            $type = 'not-defined';
        }
        return $type;
    }

}
