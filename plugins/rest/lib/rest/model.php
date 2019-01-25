<?php

class rex_yform_rest_model
{
    public $config = [];

    public static $requestMethods = ['get', 'post', 'delete'];

    public function __construct($config)
    {
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
        if (!isset($this->config['table'])) {
            \rex_yform_rest::sendError(400, 'table-not-available');
        }

        $requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
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
                    // instance
                    $id = array_shift($paths);
                    $query
                        ->where('id', $id);
                    $instance = $query->findOne();

                    if (!$instance) {
                        \rex_yform_rest::sendError(400, 'no-dataset-found', ['id' => $id, 'table' => $table->getTableName()]);
                    }

                    if (count($paths) > 0) {
                        // single property

                        $field = array_pop($paths);
                        if (in_array($field, $fields)) {
                            $field = $instance->getValue($field);
                        }
                        $content = [$field];

                        \rex_yform_rest::sendContent(200, $content);
                    }

                    // all fields of instance

                    $data = [];
                    foreach ($fields as $field) {
                        $data[$field] = $instance->getValue($field);
                    }

                    \rex_yform_rest::sendContent(200, $data);
                } else {
                    // instances

                    if (isset($get['filter']) && is_array($get['filter'])) {
                        foreach ($get['filter'] as $filterKey => $filterValue) {
                            foreach ($fields as $field) {
                                if ($field == $filterKey) {
                                    $query->where($filterKey, $filterValue);
                                }
                            }
                        }
                    }

                    // page, per_page

                    $per_page = (isset($get['per_page'])) ? (int) $get['per_page'] : (int) $table->getListAmount();
                    $per_page = ($per_page < 0) ? $per_page = $table->getListAmount() : $per_page;

                    $page = (isset($get['page'])) ? (int) $get['page'] : 1;
                    $page = ($page < 0) ? 1 : $page;

                    $query->limit(($page - 1) * $per_page, $per_page);

                    // sort_field, sort_order

                    $sortOrders = [$table->getSortOrderName()];
                    if (isset($get['sort_order']) && $get['sort_order'] != '') {
                        $sortOrders = [];
                        foreach (explode(',', $get['sort_order']) as $sort_order) {
                            if (strtolower($sort_order) != 'desc') {
                                $sort_order = 'asc';
                            }
                            $sortOrders[] = $sort_order;
                        }
                    }

                    $sortFields = [$table->getSortFieldName()];
                    if (isset($get['sort_field']) && $get['sort_field'] != '') {
                        $sortFields = [];
                        foreach (explode(',', $get['sort_field']) as $sort_field) {
                            foreach ($fields as $field) {
                                if ($field === $sort_field) {
                                    $sortFields[] = $sort_field;
                                }
                            }
                        }
                    }

                    foreach ($sortFields as $k => $sortField) {
                        $query->orderBy($sortField, (isset($sortOrders[$k])) ? $sortOrders[$k] : 'desc');
                    }

                    $instances = $query->find();

                    $collection = [];
                    foreach ($instances as $instance) {
                        $data = [];
                        foreach ($fields as $field) {
                            $data[$field] = $instance->getValue($field);
                        }
                        $collection[] = $data;
                    }

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
                    if (in_array($inKey, $fields)) {
                        $dataset->setValue($inKey, $inValue);
                    }
                }

                if ($dataset->save()) {
                    \rex_yform_rest::sendContent($OKStatus, ['id' => $dataset->getId()]);
                } else {
                    foreach ($dataset->getMessages() as $message_key => $message) {
                        $errors[] = \rex_i18n::translate($message);
                    }
                    \rex_yform_rest::sendError(400, 'errors-set', $errors);
                }
                break;

            case 'delete':

                $fields = $this->getFieldsFromModelType('delete');
                if (isset($_GET['filter']) && is_array($_GET['filter'])) {
                    $filter = false;
                    $instances = $this->config['query'];
                    foreach ($_GET['filter'] as $filterKey => $filterValue) {
                        foreach ($fields as $field) {
                            if ($field == $filterKey) {
                                $instances->where($filterKey, $filterValue);
                                $filter = true;
                            }
                        }
                    }
                    if (!$filter) {
                        \rex_yform_rest::sendError(404, 'no-available-filter-set');
                    }
                } else {
                    if (count($paths) == 0) {
                        \rex_yform_rest::sendError(404, 'no-id-set');
                    }
                    $id = $paths[0];
                    $instances = $this->config['query']
                        ->where('id', $id);
                }

                $data = $instances->find();

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
                \rex_yform_rest::sendError(404, 'no-request-method-found', ['please only use: '. implode(',', $availableMethods)]);
        }
    }

    public function getFieldsFromModelType($type)
    {
        /* @var $table \rex_yform_manager_table */
        $table = $this->config['table'];

        if (!is_object($table)) {
            throw  new rex_api_exception('Problem with Config: A Table/Class does not exists ');
        }
        $availableFields = $table->getValueFields();
        $returnFields = ['id'];
        $fields = (!isset($this->config[$type]['fields'])) ? [] : $this->config[$type]['fields'];

        dump($availableFields);

        foreach ($availableFields as $key => $availableField) {
            if ($availableField->getDatabaseFieldType() != 'none') {
                if (count($fields) == 0 || in_array($key, $fields)) {
                    $returnFields[] = $key;
                }
            }
        }

        return $returnFields;
    }
}
