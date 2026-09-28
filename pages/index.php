<?php

use Redaxo\Core\Backend\Controller;
use Redaxo\Core\View\View;
use Yakamara\YForm\YFormAddon;

/** @var YFormAddon $this */

// The data pages belong to the per-table navigation entries and render their own headline.
if ('yform/data' !== Controller::getCurrentPage()) {
    echo View::title($this->i18n('yform'));
}
Controller::includeCurrentPageSubPath();
