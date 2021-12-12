<?php

class rex_yform_rest_route
{
    public $config = [];
    public $path = '';
    public $type = '';

    /** @var rex_yform_manager_table */
    public $table;
    public $query;
    public $instance;

    private $includes;

    public static $requestMethods = ['get', 'post', 'delete'];

    private $additionalHeaders = [];

    /**
     * rex_yform_rest_route constructor.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->config['table'] = $config['type']::table();
        $this->type = $config['type'];
        $this->table = $this->config['table'];
        $this->query = $this->config['query'];
        $this->instance = $this->table->createDataset();
        $this->path = ('/' == substr($this->config['path'], -1)) ? substr($this->config['path'], 0, -1) : $this->config['path'];
        return $this;
    }

    /**
     * @return $this
     */
    public function setHeader(string $name, string $value)
    {
        $this->additionalHeaders[$name] = $value;
        return $this;
    }

    /**
     * @param        $status
     * @param        $content
     * @param string $contentType
     */
    public function sendContent($status, $content, $contentType = 'application/json')
    {
        foreach ($this->additionalHeaders as $name => $value) {
            rex_yform_rest::setHeader($name, $value);
        }

        rex_yform_rest::sendContent($status, $content, $contentType);
        exit;
    }

    public function hasAuth(): bool
    {
        if (isset($this->config['auth'])) {
            if (is_callable($this->config['auth'])) {
                return call_user_func($this->config['auth'], $this);
            }
            return $this->config['auth'];
        }
        return true;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param $paths
     * @param $get
     * @throws rex_api_exception
     */
    public function handleRequest(array $paths, array $get)
    {
        if (!isset($this->config['table'])) {
            rex_yform_rest::sendError('400', 'table-not-available');
        }

        $requestMethod = $this->getRequestMethod();
        if (in_array($requestMethod, self::$requestMethods) && !isset($this->config[$requestMethod])) {
            rex_yform_rest::sendError('400', 'request-method-not-available');
        }

        /** @var rex_yform_manager_table $table */
        $table = $this->config['table'];

        /** @var rex_yform_manager_query $query */
        $query = $this->config['query'];

        switch ($requestMethod) {
            case 'get':
                $instance = $table->createDataset();
                $fields = $this->getFields('get', $instance);

                /** @var rex_yform_manager_dataset $instance */
                $instance = null;
                /** @var rex_yform_manager_collection $instance */
                $instances = null;
                $attribute = null;
                $baseInstances = false;
                $itemsAll = 0;

                if (0 == count($paths)) {
                    $baseInstances = true;

                    // Base Instances with filter and order
                    $query = $this->getFilterQuery($query, $fields, $get);
                    $itemsAll = $query->count();

                    $per_page = (isset($get['per_page'])) ? (int) $get['per_page'] : (int) $table->getListAmount();
                    $per_page = ($per_page < 0) ? $per_page = $table->getListAmount() : $per_page;

                    $currentPage = (isset($get['page'])) ? (int) $get['page'] : 1;
                    $currentPage = ($currentPage < 0) ? 1 : $currentPage;

                    $query->limit(($currentPage - 1) * $per_page, $per_page);

                    $order = [];
                    if (isset($get['order']) && is_array($get['order'])) {
                        foreach ($get['order'] as $orderName => $orderValue) {
                            if (array_key_exists($orderName, $fields)) {
                                $orderValue = ('desc' != $orderValue) ? 'asc' : 'desc';
                                $order[$orderName] = $orderValue;
                                $query->orderBy($orderName, $orderValue);
                            }
                        }
                        if (0 == count($order)) {
                            $order[$table->getSortFieldName()] = $table->getSortOrderName();
                        }
                        $query->orderBy($table->getSortFieldName(), $table->getSortOrderName());
                    }

                    $instances = $query->find();
                }

                /*
                 * examples:
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

                foreach ($paths as $path) {
                    if ($instances) {
                        $id = $path;
                        foreach ($instances as $i_instance) {
                            if ($i_instance->getId() == $id) {
                                $instance = $i_instance;
                            }
                        }

                        if (!$instance) {
                            rex_yform_rest::sendError('400', 'dataset-not-found', ['paths' => $paths, 'table' => $instances->getTable()->getTableName()]);
                        }
                        $attribute = null;
                        $instances = null;
                    } elseif (!$instance) {
                        $id = $path;
                        $id_column = 'id';
                        if ('' != $query->getTableAlias()) {
                            $id_column = $query->getTableAlias().'.id';
                        }

                        $query
                            ->where($id_column, $id);
                        $instance = $query->findOne();

                        if (!$instance) {
                            rex_yform_rest::sendError('400', 'dataset-not-found', ['paths' => $paths, 'table' => $query->getTable()->getTableName()]);
                        }

                        $fields = $this->getFields('get', $instance);
                        $attribute = null;
                    } else {
                        $attribute = $path;

                        if (!array_key_exists($attribute, $fields)) {
                            rex_yform_rest::sendError('400', 'attribute-not-found', ['paths' => $paths, 'table' => $table->getTableName()]);
                        }

                        if ('be_manager_relation' == $fields[$attribute]->getTypeName()) {
                            echo $attribute;
                            $instances = $instance->getRelatedCollection($attribute);
                            if (count($instances) > 0) {
                                $instance = $instances->current();
                            }
                            $fields = $this->getFields('get', $instance);
                            $instance = null;
                        }
                    }
                }

                $data = [];
                if ($instances) {
                    foreach ($instances as $instance) {
                        $data[] = $this->getInstanceData(
                            $instance,
                            array_merge($paths, [$instance->getId()])
                        );
                    }

                    if ($baseInstances) {
                        $links = [];
                        $meta = [];

                        $linkParams = [
                            'page' => $currentPage,
                            'per_page' => $per_page,
                            'order' => $order,
                        ];

                        if (isset($get['filter']) && is_array($get['filter'])) {
                            $linkParams['filter'] = $get['filter'];
                            $meta['filter'] = $get['filter'];
                        }

                        if ($order) {
                            $meta['order'] = $order;
                        }

                        $meta['totalItems'] = (int) $itemsAll;
                        $meta['currentItems'] = count($instances);
                        $meta['itemsPerPage'] = $per_page;
                        $meta['currentPage'] = $currentPage;

                        $links['self'] = rex_yform_rest::getLinkByPath($this, $linkParams);
                        $links['first'] = rex_yform_rest::getLinkByPath($this, array_merge(
                            $linkParams,
                            ['page' => 1]
                        ));
                        if (($currentPage - 1) > 0) {
                            $links['prev'] = rex_yform_rest::getLinkByPath($this, array_merge(
                                $linkParams,
                                ['page' => ($currentPage - 1)]
                            ));
                        }
                        if (($currentPage * $per_page) < $itemsAll) {
                            $links['next'] = rex_yform_rest::getLinkByPath($this, array_merge(
                                $linkParams,
                                ['page' => ($currentPage + 1)]
                            ));
                        }

                        $data = [
                            'links' => $links,
                            'meta' => $meta,
                            'data' => $data,
                        ];
                    }
                } elseif ($instance) {
                    if ($attribute) {
                        $data = $instance->getValue($attribute);
                    } else {
                        $data = $this->getInstanceData(
                            $instance,
                            array_merge($paths)
                        );
                    }
                }

                $this->sendContent('200', $data);

                break;

            // ----- /END GET

            case 'post':
                $instance = $table->createDataset();

                $errors = [];
                $fields = $this->getFields('post', $instance);

                $in = json_decode(file_get_contents('php://input'), true);

                $data = (array) @$in['data']['attributes'];
                $type = (string) @$in['data']['type'];
                $status = '400';

                if (self::getTypeFromInstance($instance) != $type) {
                    rex_yform_rest::sendError($status, 'post-data-type-different');
                }

                if (0 == count($data)) {
                    rex_yform_rest::sendError($status, 'post-data-attributes-empty');
                } else {
                    $dataset = null;
                    if (isset($in['id'])) {
                        $dataset = $table->getDataset($in['id']);
                        $status = '200'; // update
                    }

                    if (!$dataset) {
                        if (isset($in['id'])) {
                            $dataset = $table->getRawDataset($in['id']);
                        }
                        if (!$dataset) {
                            $dataset = $table->createDataset();
                        }
                        $status = '201'; // created
                    }

                    foreach ($data as $inKey => $inValue) {
                        if (array_key_exists($inKey, $fields) && 'be_manager_relation' != $fields[$inKey]->getTypeName()) {
                            $dataset->setValue($inKey, $inValue);
                        }
                    }

                    $relations = (array) @$in['data']['relationships'];

                    foreach ($relations as $inKey => $inValue) {
                        if (array_key_exists($inKey, $fields) && 'be_manager_relation' == $fields[$inKey]->getTypeName()) {
                            $relation_data = @$inValue['data'];
                            if (!is_array($relation_data)) {
                                $relation_data = [$relation_data];
                            }

                            $value = [];
                            foreach ($relation_data as $relation_date) {
                                $relation_date_type = (string) @$relation_date['type'];
                                // TODO: übergebenen Type mit Klasse der Relation prüfen

                                $relation_date_id = (int) @$relation_date['id'];
                                if ($relation_date_id > 0) {
                                    $value[] = $relation_date_id;
                                }
                            }
                            $dataset->setValue($inKey, implode(',', $value));
                        }
                    }

                    if ($dataset->save()) {
                        rex_yform_rest::sendContent($status, ['id' => $dataset->getId()]);
                    } else {
                        foreach ($dataset->getMessages() as $message_key => $message) {
                            $errors[] = rex_i18n::translate($message);
                        }
                        rex_yform_rest::sendError($status, 'errors-set', $errors);
                    }
                }

                break;

            case 'delete':
                $instance = $table->createDataset();
                $fields = $this->getFields('delete', $instance);
                $status = '404';

                $queryClone = clone $query;
                $query = $this->getFilterQuery($query, $fields, $get);

                if ($queryClone === $query && isset($get['filter'])) {
                    rex_yform_rest::sendError($status, 'no-available-filter-set');
                } elseif ($queryClone->getQuery() !== $query->getQuery()) {
                    // filter set -> true
                    $status = '200';
                } elseif (0 == count($paths)) {
                    rex_yform_rest::sendError($status, 'no-id-set');
                } else {
                    $id = current($paths);
                    $query->where('id', $id);
                    $status = '200';
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
                        ++$content['failed'];
                    }
                    $content['dataset'][] = $date;
                }

                rex_yform_rest::sendContent($status, $content);

                break;

            default:
                $availableMethods = [];
                foreach (self::$requestMethods as $method) {
                    if (isset($this->config[$method])) {
                        $availableMethods[] = strtoupper($method);
                    }
                }
                rex_yform_rest::sendError('404', 'no-request-method-found', ['please only use: ' . implode(',', $availableMethods)]);
        }
    }

    /**
     * @param $instance
     * @throws rex_api_exception
     * @return rex_yform_manager_field[]
     */
    public function getFields(string $type = 'get', $instance = null): array
    {
        $class = $this->getTypeFromInstance($instance);

        $returnFields = ['id' => new rex_yform_manager_field([
            'name' => 'id',
            'type_id' => 'value',
            'type_name' => 'integer',
        ])];

        if (!isset($this->config[$type]['fields'][$class])) {
            return $returnFields;
        }

        /** @var rex_yform_manager_table $table */
        /** @var rex_yform_manager_dataset $class */
        $table = $class::table();

        if (!is_object($table)) {
            throw new rex_api_exception('Problem with Config: A Table/Class does not exists ');
        }

        $availableFields = $table->getValueFields();

        foreach ($availableFields as $key => $availableField) {
            if ('none' != $availableField->getDatabaseFieldType()) {
                // ALLE Felder erlaubt wenn kein Feld gesetzt ? count($this->config[$type]['fields'][$class]) == 0 ||
                if (isset($this->config[$type]['fields'][$class]) && in_array($key, @$this->config[$type]['fields'][$class], true)) {
                    $returnFields[$key] = $availableField;
                }
            }
        }

        return $returnFields;
    }

    /**
     * @param $query
     * @param $fields
     * @param $get
     */
    public function getFilterQuery($query, $fields, $get): rex_yform_manager_query
    {
        /** @var rex_yform_manager_query $query */
        $tableAlias = $query->getTableAlias();

        if (isset($get['filter']) && is_array($get['filter'])) {
            foreach ($get['filter'] as $filterKey => $filterValue) {
                foreach ($fields as $fieldName => $field) {
                    /* @var rex_yform_manager_field $field */

                    if ($fieldName == $filterKey) {
                        if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getSearchFilter')) {
                            try {
                                $rawQuery = $field->getObject()->getSearchFilter([
                                    'value' => $filterValue,
                                    'field' => $field,
                                ]);

                                if ('' != $tableAlias) {
                                    // TODO: fieser hack bisher, da bekannt wie die SearchFilter funktionieren.
                                    $rawQuery = str_replace('`'.$field.'`', '`'.$tableAlias.'`.`'.$field.'`', $rawQuery);
                                }
                            } catch (Error $e) {
                                rex_yform_rest::sendError('400', 'field-class-not-found', ['field' => $fieldName]);
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

    /**
     * @param       $instance
     * @param       $paths
     * @param bool $onlyId
     */
    public function getInstanceData($instance, $paths, $onlyId = false, $parents = []): array
    {
        $links = [];
        $links['self'] = rex_yform_rest::getLinkByPath($this, [], $paths);

        if ($onlyId) {
            return
                [
                    'id' => $instance->getId(),
                    'type' => $this->getTypeFromInstance($instance),
                    'links' => $links,
                ];
        }
        return
                [
                    'id' => $instance->getId(),
                    'type' => $this->getTypeFromInstance($instance),
                    'attributes' => $this->getInstanceAttributes($instance, $parents),
                    'relationships' => $this->getInstanceRelationships($instance, $parents),
                    'links' => $links,
                ];
    }

    /**
     * @throws rex_api_exception
     */
    public function getInstanceAttributes(rex_yform_manager_dataset $instance, $parents = []): array
    {
        $data = [];

        $fields = $this->getFields('get', $instance);
        $fields = $this->filterFieldsByInclude($fields, $parents);

        foreach ($fields as $fieldName => $field) {
            if ('be_manager_relation' != $field->getTypeName()) {
                $data[$fieldName] = $instance->getValue($field->getName());
            }
        }

        return $data;
    }

    private function getIncludes(): array
    {
        if (null === $this->includes) {
            $includes = @rex_request('include', 'string', '');
            if ('' == $includes) {
                $this->includes = [];
            } else {
                foreach (explode(',', $includes) as $include) {
                    $this->includes[$include] = $include;
                    while (false !== strrpos($include, '.')) {
                        $include = substr($include, 0, strrpos($include, '.'));
                        $this->includes[$include] = $include;
                    }
                }
            }
        }
        return $this->includes;
    }

    private function filterFieldsByInclude(array $fields, array $parents = []): array
    {
        if (0 == count($this->getIncludes())) {
            return $fields;
        }

        $newFields = [];
        foreach ($fields as $key => $field) {
            $compareKey = 0 == count($parents) ? $key : implode('.', $parents).'.'.$key;
            if (in_array($compareKey, $this->getIncludes(), true)) {
                $newFields[$key] = $field;
            }
        }

        return $newFields;
    }

    /**
     * @throws rex_api_exception
     */
    public function getInstanceRelationships(rex_yform_manager_dataset $instance, $parents = []): array
    {
        $paths[] = $instance->getId();

        $fields = $this->getFields('get', $instance);
        $fields = $this->filterFieldsByInclude($fields, $parents);

        $return = [];

        foreach ($fields as $field) {
            if ('be_manager_relation' == $field->getTypeName()) {
                $fieldParents = $parents;
                $fieldParents[] = $field->getName();

                $relationInstances = $instance->getRelatedCollection($field->getName());

                $data = [];
                foreach ($relationInstances as $relationInstance) {
                    $onlyId = false;
                    if ($this->table->getTableName() == $relationInstance->getTableName()) {
                        $onlyId = true;
                    }
                    $data[] = $this->getInstanceData(
                        $relationInstance,
                        array_merge($paths, [$field->getName(), $relationInstance->getId()]),
                        $onlyId,
                        $fieldParents
                    );
                }
                $return[$field->getName()] = [
                    'data' => $data,
                ];

                $links = [];
                $links['self'] = rex_yform_rest::getLinkByPath($this, [], array_merge($paths, [$field->getName()]));

                if (isset($relationInstance)) {
                    $route = rex_yform_rest::getRouteByInstance($relationInstance);

                    if ($route) {
                        $links['absolute'] = rex_yform_rest::getLinkByPath($route, []);
                    }
                }

                $return[$field->getName()]['links'] = $links;
            }
        }

        return $return;
    }

    /**
     * @param       $instance
     * @param       $key
     * @param false $attributCall
     * @return mixed
     */
    public function getInstanceValue($instance, $key, $attributCall = false)
    {
        return $instance->getValue($key, $attributCall);
    }

    public function getRequestMethod(): string
    {
        if (isset($_SERVER['X-HTTP-Method-Override'])) {
            return strtolower($_SERVER['X-HTTP-Method-Override']);
        }
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * @param mixed $instance
     */
    public function getTypeFromInstance($instance = null): string
    {
        if (!$instance) {
            $type = 'not-defined';
        } else {
            $type = get_class($instance);
            if ('rex_yform_manager_dataset' == $type || 'rex_yform_rest_route' == $instance || !$type) {
                $type = 'not-defined';
            }
        }
        return $type;
    }
}
