<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @var rex_plugin $this
 * @psalm-scope-this rex_plugin
 */

// rex_yform::addTemplatePath(rex_path::plugin('yform','geo','ytemplates'));

if (rex::isBackend() && rex::getUser()) {
    rex_view::addJsFile($this->getAssetsUrl('daterangepicker/moment.min.js'));
    rex_view::addJsFile($this->getAssetsUrl('daterangepicker/daterangepicker.js'));
    rex_view::addCssFile($this->getAssetsUrl('daterangepicker/daterangepicker.css'));
    rex_view::addJsFile($this->getAssetsUrl('inputmask/dist/jquery.inputmask.min.js'));
    rex_view::addJsFile($this->getAssetsUrl('tools.js'));
}
