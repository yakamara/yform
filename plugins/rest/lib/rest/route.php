<?php

/*
 *
 */

class rex_yform_rest_route
{
    public static $routes = [];
    public $path = '';
    public $model = null;

    public static function factory()
    {
        return new self();
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function addModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function handleRequest(array $paths, $get)
    {
        /* @var $model \rex_yform_rest_model */
        $model = $this->model;

        if (!$model->hasAuth()) {
            \rex_yform_rest::sendError(400, 'no-access');
        } else {
            $model
                ->handleRequest($paths, $get);
        }

        exit;
    }
}
