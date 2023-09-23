<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class rex_yform_base_abstract
{
    public $id;
    public $params = [];
    public $obj;
    public $elements;
    protected $elementMapping;

    public function loadParams(&$params, $elements)
    {
        $this->params = &$params;
        $offset = 0;
        foreach ($elements as $key => $value) {
            if (is_string($value) && !empty($value) && '#' == $value[0] && str_contains($value, ':')) {
                [$key, $value] = explode(':', mb_substr($value, 1), 2);
                ++$offset;
            }
            $this->setElement(is_numeric($key) ? $key - $offset : $key, $value);
        }
    }

    protected function loadElementMapping()
    {
        if (null !== $this->elementMapping) {
            return;
        }

        $this->elementMapping = [];
        $definitions = $this->getDefinitions();
        if (isset($definitions['values'])) {
            $i = $this->getElementMappingOffset();
            foreach ($definitions['values'] as $key => $_) {
                $this->elementMapping[$i] = is_int($key) ? $i : $key;
                ++$i;
            }
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    abstract protected function getElementMappingOffset();

    public function setElement($i, $v)
    {
        $this->loadElementMapping();
        if (is_int($i) && isset($this->elementMapping[$i])) {
            $i = $this->elementMapping[$i];
        }
        $this->elements[$i] = $v;
    }

    public function getElement($i)
    {
        if (isset($this->elements[$i])) {
            return $this->elements[$i];
        }
        if (isset($this->elementMapping[$i]) && isset($this->elements[$this->elementMapping[$i]])) {
            return $this->elements[$this->elementMapping[$i]];
        }
        return false;
    }

    public function getParam($param, $default = null)
    {
        return $this->params[$param] ?? $default;
    }

    public function setObjects(&$obj)
    {
        $this->obj = &$obj;
    }

    public function getObjects()
    {
        return $this->obj;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getDefinitions(): array
    {
        return [];
    }

    public function preValidateAction(): void
    {
    }

    public function postValidateAction(): void
    {
    }

    public function postValueAction(): void
    {
    }

    public function postFormAction(): void
    {
    }

    public function preAction(): void
    {
    }

    public function executeAction(): void
    {
    }

    public function postAction(): void
    {
    }

    public function isDeprecated(): bool
    {
        return false;
    }

    public function init()
    {
    }
}
