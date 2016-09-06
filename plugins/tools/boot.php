<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @var rex_plugin $this
 */

// rex_yform::addTemplatePath(rex_path::plugin('yform','geo','ytemplates'));

if (rex::isBackend() && rex::getUser()) {
    rex_view::addJsFile($this->getAssetsUrl('bootstrap-daterangepicker/moment.min.js'));
    rex_view::addJsFile($this->getAssetsUrl('bootstrap-daterangepicker/daterangepicker.js'));
    rex_view::addCssFile($this->getAssetsUrl('bootstrap-daterangepicker/daterangepicker.css'));

    rex_view::addJsFile($this->getAssetsUrl('select2/dist/js/select2.min.js'));
    rex_view::addCssFile($this->getAssetsUrl('select2/dist/css/select2.min.css'));

    rex_view::addCssFile($this->getAssetsUrl('select2-bootstrap-theme/dist/select2-bootstrap.min.css'));

    rex_view::addJsFile($this->getAssetsUrl('inputmask/dist/min/jquery.inputmask.bundle.min.js'));

    rex_view::addJsFile($this->getAssetsUrl('tools.js'));
}

