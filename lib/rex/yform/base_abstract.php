<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class rex_yform_base_abstract
{
    var $params = array();
    var $obj;
    var $elements;
    protected $elementMapping;

    function loadParams(&$params, $elements)
    {
        $this->params = &$params;
        $offset = 0;
        foreach ($elements as $key => $value) {
            if (is_string($value) && !empty($value) && $value[0] == '#' && false !== strpos($value, ':')) {
                list($key, $value) = explode(':', substr($value, 1), 2);
                $offset++;
            }
            $this->setElement(is_numeric($key) ? $key - $offset : $key, $value);
        }
    }

    protected function loadElementMapping()
    {
        if (!is_null($this->elementMapping)) {
            return;
        }

        $this->elementMapping = array();
        $definitions = $this->getDefinitions();
        if (isset($definitions['values'])) {
            $i = $this->getElementMappingOffset();
            foreach ($definitions['values'] as $key => $_) {
                $this->elementMapping[$i] = is_int($key) ? $i : $key;
                $i++;
            }
        }
    }

    abstract protected function getElementMappingOffset();

    function setElement($i, $v)
    {
        $this->loadElementMapping();
        if (is_int($i) && isset($this->elementMapping[$i])) {
            $i = $this->elementMapping[$i];
        }
        $this->elements[$i] = $v;
    }

    function getElement($i)
    {
        if (isset($this->elements[$i])) {
            return $this->elements[$i];
        }
        if (isset($this->elementMapping[$i]) && isset($this->elements[$this->elementMapping[$i]])) {
            return $this->elements[$this->elementMapping[$i]];
        }
        return false;
    }

    function getParam($param)
    {
        return $this->params[$param];
    }

    function setObjects(&$obj)
    {
        $this->obj = &$obj;
    }

    function getObjects()
    {
        return $this->obj;
    }

    function getDescription()
    {
        return '';
    }

    function getDefinitions()
    {
        return array();
    }

    function preValidateAction() {}

    function postValidateAction() {}

    function postValueAction() {}

    function postFormAction() {}

    function preAction() {}

    function executeAction()
    {
        return $this->execute();
    }

    function postAction() {}

    /* deprecated */
    function execute() {}

}
