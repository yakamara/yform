<?php

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
    protected static $additionalHeaders = [];
    protected static $routes = [];

    public static function addRoute(rex_yform_rest_route $route)
    {
        self::$routes[] = $route;
    }

    /**
     * @return array
     */
    public static function getRoutes()
    {
        return self::$routes;
    }

    /**
     * @return mixed|string
     */
    public static function getCurrentPath()
    {
        $url = parse_url($_SERVER['REQUEST_URI']);
        if (isset($url['path']) && class_exists('\rex_yrewrite') && rex_yrewrite::getCurrentDomain()) {
            $currentPath = str_replace(rex_yrewrite::getCurrentDomain()->getPath(), '/', $url['path']);
        } else {
            $currentPath = '';
        }
        return rtrim($currentPath, '/') . '/';
    }

    /**
     * @return bool
     */
    public static function handleRoutes()
    {
        if ('' != self::$preRoute) {
            if (mb_substr(self::getCurrentPath(), 0, mb_strlen(self::$preRoute)) != self::$preRoute) {
                return false;
            }
        }

        foreach (self::$routes as $route) {
            $routePath = self::$preRoute . $route->getPath();
            $routePath = rtrim($routePath, '/') . '/';

            if (mb_substr(self::getCurrentPath(), 0, mb_strlen($routePath)) != $routePath) {
                continue;
            }

            $paths = explode('/', mb_substr(self::getCurrentPath(), mb_strlen($routePath)));

            $paths = array_filter($paths, static function ($p) {
                if (!empty($p)) {
                    return true;
                }
                return false;
            });

            /** @var \rex_yform_rest_route $route */

            if (!$route->hasAuth()) {
                self::sendError('400', 'no-access');
            } else {
                $route
                ->handleRequest($paths, $_GET);
            }
        }
        return true;
    }

    public static function setHeader(string $name, string $value)
    {
        self::$additionalHeaders[$name] = $value;
    }

    /**
     * @param string $status
     * @param string $error
     * @param array  $descriptions
     */
    public static function sendError($status = '404', $error = 'error', $descriptions = [])
    {
        $message = [];
        $message['errors'] = [
            'message' => $error,
            'status' => $status,
            'descriptions' => $descriptions,
        ];
        self::sendContent($status, $message);
    }

    /**
     * @param string $contentType
     */
    public static function sendContent($status, $content, $contentType = 'application/json')
    {
        foreach (self::$additionalHeaders as $name => $value) {
            rex_response::setHeader($name, $value);
        }

        rex_response::setStatus(self::$status[$status]);
        rex_response::sendContent(json_encode($content), $contentType);
        exit;
    }

    /**
     * @param string $key
     * @param string $default
     * @return mixed|string
     */
    public static function getHeader($key = '', $default = '')
    {
        $value = '';
        $headers = [];

        foreach ($_SERVER as $k => $v) {
            if ('HTTP_' == mb_substr($k, 0, 5)) {
                $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', mb_substr($k, 5))))] = $v;
            } elseif ('CONTENT_TYPE' == $k) {
                $headers['Content-Type'] = $v;
            } elseif ('CONTENT_LENGTH' == $k) {
                $headers['Content-Length'] = $v;
            }
        }

        if (array_key_exists($key, $headers)) {
            $value = $headers[$key];
        }

        if ('' == $value) {
            $value = rex_get($key, 'string', '');
        }

        if ('' == $value) {
            $value = $default;
        }

        return $value;
    }

    /**
     * @param array $params
     * @param array $additionalPaths
     */
    public static function getLinkByPath(rex_yform_rest_route $route, $params = [], $additionalPaths = []): string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO']) {
            $url = 'https://';
        } elseif ((isset($_SERVER['SERVER_PORT']) && 443 == $_SERVER['SERVER_PORT']) || (isset($_SERVER['HTTPS']) && 'off' != strtolower($_SERVER['HTTPS']))) {
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
        $query = ('' != $query) ? '?' . $query : $query;

        $path = implode('/', array_merge([$route->getPath()], $additionalPaths));

        return $url . self::$preRoute . $path . $query;
    }

    /**
     * @return null|mixed
     */
    public static function getRouteByInstance($instance)
    {
        $instanceType = $instance::class;

        foreach (self::$routes as $route) {
            if ($route->type == $instanceType) {
                return $route;
            }
        }

        return null;
    }

    public static function getCurrentUrl(): string
    {
        return $_SERVER['REQUEST_URI'];
    }
}
