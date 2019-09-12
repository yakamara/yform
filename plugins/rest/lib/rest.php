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

    public static function getRoutes()
    {
        return self::$routes;
    }

    public static function getCurrentPath()
    {
        $url = parse_url($_SERVER['REQUEST_URI']);
        return $url['path'];
    }

    public static function handleRoutes()
    {
        if (self::$preRoute != '') {
            if (substr(self::getCurrentPath(), 0, strlen(self::$preRoute)) != self::$preRoute) {
                return false;
            }
        }

        foreach (self::$routes as $route) {
            $routePath = self::$preRoute . $route->getPath();

            if (substr(self::getCurrentPath(), 0, strlen($routePath)) != $routePath) {
                continue;
            }

            $paths = explode('/', substr(self::getCurrentPath(), strlen($routePath)));

            $paths = array_filter($paths, function ($p) {
                if (!empty($p)) {
                    return true;
                }
                return false;
            });

            /* @var $route \rex_yform_rest_route */

            if (!$route->hasAuth()) {
                self::sendError(400, 'no-access');
            } else {
                $route
                ->handleRequest($paths, $_GET);
            }

        }
    }

    public static function sendError($status = '404', $error = 'error', $descriptions = [])
    {
        $message = [];
        $message['errors'] = [
            'message' => $error,
            'status' => $status,
            'descriptions' => $descriptions
        ];
        self::sendContent($status, $message);
    }

    public static function sendContent($status, $content, $contentType = 'application/json')
    {
        \rex_response::setStatus(self::$status[$status]);
        \rex_response::sendContent(json_encode($content), $contentType);
        exit;
    }

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

    public static function getLinkByPath($route, $params = [], $additionalPaths = [])
    {

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $url = 'https://';
        } elseif ( (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off')) {
            $url = 'https://';
        } else {
            $url = 'http://';
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
            $url .= $_SERVER['HTTP_X_FORWARDED_SERVER'];
        } else {
            $url .= @$_SERVER['HTTP_HOST'];
        }

        $query = http_build_query($params, '', '&');
        $query = ($query != '') ? '?' . $query : $query;

        $path = implode('/', array_merge([$route->getPath()], $additionalPaths));

        return $url . self::$preRoute . $path . $query ;

    }

    public static function getRouteByInstance($instance)
    {
        $instanceType = get_class($instance);

        foreach (self::$routes as $route) {
            if ($route->type == $instanceType) {
                return $route;
            }
        }

        return null;

    }

    public static function getCurrentUrl()
    {
        return $_SERVER['REQUEST_URI'];

    }

}
