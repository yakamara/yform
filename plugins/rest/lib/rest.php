<?php

/*
 *
 */

class rex_yform_rest
{
    protected $config = [];
    protected $route = '';
    public static $status = [
        200 => '200 OK',
        201 => '201 Created', // for POST Created resource with Link
        // 201 – OK – New resource has been created
        204 => '204 No Content',
        // 204 – OK – The resource was successfully deleted
        304 => '304 – Not Modified',
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        500 => '500 Internal Server Error',
    ];
    public static $preRoute = '/rest';

    protected static $routes = [];

    public static function addRoute($route)
    {
        self::$routes[] = $route;
    }

    public static function handleRoutes()
    {
        $url = parse_url($_SERVER['REQUEST_URI']);

        if (self::$preRoute != '') {
            if (substr($url['path'], 0, strlen(self::$preRoute)) != self::$preRoute) {
                return false;
            }
        }

        foreach (self::$routes as $route) {
            $routePath = self::$preRoute.$route->getPath();

            if (substr($url['path'], 0, strlen($routePath)) != $routePath) {
                continue;
            }

            $paths = explode('/', substr($url['path'], strlen($routePath)));

            $paths = array_filter($paths, function ($p) {
                if (!empty($p)) {
                    return true;
                }
                return false;
            });

            $route->handleRequest($paths, $_GET);
        }
    }

    public static function sendError($status = '404', $error = 'error', $error_descriptions = [])
    {
        $message = [];
        $message['error'] = $error;
        $message['status'] = $status;
        $message['error_messages'] = $error_descriptions;
        self::sendContent($status, $message);
    }

    public static function sendContent($status, array $content, $contentType = 'application/json')
    {
        \rex_response::setStatus(\rex_yform_rest::$status[$status]);
        \rex_response::sendContent(json_encode($content), $contentType);
        exit;
    }

    /*
    public function getInfoView()
    {
        $elements = [];

        foreach ($this->config['models'] as $modelName => $params) {
            if ($this->hasTokenAccess($params)) {
                $Path = 'http[s]://'.$_SERVER['HTTP_HOST'].$this->config['route'].$modelName.'/';
                $attributes = ['token' => 'mytoken'];

                $contentModel = [];

                if (isset($params['get'])) {
                    $contentModel[] = $this->getGetInfoView($Path, $params, $attributes);
                }

                if (isset($params['post'])) {
                    $contentModel[] = $this->getPostInfoView($Path, $params, $attributes);
                }

                if (isset($params['delete'])) {
                    $contentModel[] = $this->getDeleteInfoView($Path, $params, $attributes);
                }

                $elements[] = [
                    'model' => $modelName,
                    'types' => $contentModel,
                ];
            }
        }

        $fragment = new \rex_fragment();
        $fragment->setVar('elements', $elements, false);

        \rex_response::sendContent($fragment->parse('rest_api.php'));
        exit;
    }

    public function getGetInfoView($path, $params, $attributes)
    {
        $elements[] = [
            'type' => 'GET',
            'path' => $path,
            'header' => $attributes,
            'fields' => $this->getFieldsFromModelType($params, 'get'),
        ];

        $elements[] = [
            'type' => 'GET',
            'path' => $path,
            'headers' => array_merge($attributes, ['filter[myfieldname]' => 'myvalue', 'sort' => 'id', 'direction' => 'asc', 'offset' => 0, 'limit' => 10]),
            'fields' => $this->getFieldsFromModelType($params, 'get'),
        ];

        $elements[] = [
            'type' => 'GET',
            'path' => $path.'{id}/',
            'headers' => $attributes,
            'fields' => $this->getFieldsFromModelType($params, 'get'),
        ];

        $elements[] = [
            'type' => 'GET',
            'path' => $path.'{id}/{field}/',
            'headers' => $attributes,
            'fields' => $this->getFieldsFromModelType($params, 'get'),
        ];

        $fragment = new \rex_fragment();
        $fragment->setVar('elements', $elements, false);
        return $fragment->parse('rest_api_type.php');
    }

    public function getPostInfoView($path, $params, $attributes)
    {
        $elements[] = [
            'type' => 'POST',
            'path' => $path,
            'headers' => $attributes,
            'content_type' => 'Content-Type: application/json',
            'fields' => $this->getFieldsFromModelType($params, 'post'),
            'body' => '{ "fieldname": "value", "fieldname2": "value2" }',
        ];

        $fragment = new \rex_fragment();
        $fragment->setVar('elements', $elements, false);
        return $fragment->parse('rest_api_type.php');
    }

    public function getDeleteInfoView($path, $params, $attributes)
    {
        $elements[] = [
            'type' => 'DELETE',
            'path' => $path.'{id}/',
            'headers' => $attributes,
        ];

        $elements[] = [
            'type' => 'DELETE',
            'path' => $path,
            'headers' => array_merge(['filter[fieldname]' => 'test'], $attributes),
            'fields' => $this->getFieldsFromModelType($params, 'delete'),
        ];
        $fragment = new \rex_fragment();
        $fragment->setVar('elements', $elements, false);
        return $fragment->parse('rest_api_type.php');
    }

    */

    // Helper Methods

    public static function getHeader($key = '', $default = '')
    {
        $value = '';

        $headers = [];

        foreach ($_SERVER as $k => $v) {
            if (substr($k, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($k, 5))))] = $v;
            } elseif ($k == 'CONTENT_TYPE') {
                $headers['Content-Type'] = $v;
            } elseif ($k == 'CONTENT_LENGTH') {
                $headers['Content-Length'] = $v;
            }
        }

        if (array_key_exists($key, $headers)) {
            $value = $headers[$key];
        }

        if ($value == '') {
            $value = rex_get($key, 'string', '');
        }

        if ($value == '') {
            $value = $default;
        }

        return $value;
    }
}
