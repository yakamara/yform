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

    //rex_view::addCssFile($this->getAssetsUrl('select2-bootstrap-theme/dist/select2-bootstrap.min.css'));

    rex_view::addJsFile($this->getAssetsUrl('inputmask/dist/min/jquery.inputmask.bundle.min.js'));

    rex_view::addJsFile($this->getAssetsUrl('tools.js'));


    rex_extension::register('PACKAGES_INCLUDED', function () {
        if ($this->getProperty('compile')) {
            $compiler = new rex_scss_compiler();

            $scss_files = [$this->getPath('scss/master.scss')];
            $compiler->setRootDir($this->getPath('scss/'));
            $compiler->setScssFile($scss_files);
            $compiler->setCssFile($this->getPath('assets/css/styles.css'));
            $compiler->compile();
            rex_file::copy($this->getPath('assets/css/styles.css'), $this->getAssetsPath('css/styles.css'));
        }
    });
    rex_view::addCssFile($this->getAssetsUrl('css/styles.css'));
}

