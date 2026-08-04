<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\AbstractBase;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

abstract class AbstractAction extends AbstractBase
{
    public $action = [];

    protected function getElementMappingOffset()
    {
        return 1;
    }
}
