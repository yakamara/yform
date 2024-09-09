<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\AbstractBase;

abstract class AbstractAction extends AbstractBase
{
    public $action = [];

    protected function getElementMappingOffset()
    {
        return 1;
    }
}
