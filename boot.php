<?php

/**
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

rex_yform::addTemplatePath(rex_path::addon('yform', 'ytemplates'));

if (rex::isBackend() && rex::getUser()) {
    /* @var $this rex_addon */
    rex_view::addCssFile($this->getAssetsUrl('yform.css'));
    rex_view::addCssFile($this->getAssetsUrl('yform-formbuilder.css'));

    rex_extension::register('PACKAGES_INCLUDED', function () {
        if ($this->getProperty('compile')) {
            $compiler = new rex_scss_compiler();
            $compiler->setRootDir($this->getPath('scss/'));
            $compiler->setScssFile($this->getPath('scss/yform.scss'));
            $compiler->setCssFile($this->getPath('assets/yform.css'));
            $compiler->compile();
            $compiler->setScssFile($this->getPath('scss/yform-formbuilder.scss'));
            $compiler->setCssFile($this->getPath('assets/yform-formbuilder.css'));
            $compiler->compile();
            rex_dir::copy($this->getPath('assets'), $this->getAssetsPath()); // copy whole assets directory
        }
    });

    rex_extension::register('PAGE_CHECKED', static function (rex_extension_point $ep) {
        $page = rex_be_controller::getPageObject('yform');

        if (!$page) {
            return;
        }

        $subpages = $page->getSubpages();
        if (isset($subpages['manager']) && rex::getUser()->isAdmin()) {
            $manager = $subpages['manager'];
            unset($subpages['manager']);
            $subpages = array_merge(['manager' => $manager], $subpages);
            $page->setSubpages($subpages);
        }
        if (!$subpages || 1 === count($subpages) && isset($subpages['manager'])) {
            $page->setHidden(true);
        }
    });
}
