<?php

rex_yform::addTemplatePath(rex_path::addon('yform', 'ytemplates'));

if (rex::isBackend() && rex::getUser()) {
    /* @var $this rex_addon */
    rex_view::addCssFile($this->getAssetsUrl('yform.css'));
    rex_view::addCssFile($this->getAssetsUrl('yform-formbuilder.css'));

    rex_extension::register('PAGE_CHECKED', static function (rex_extension_point $ep) {
        $page = rex_be_controller::getPageObject('yform');
        $subpages = $page->getSubpages();
        if (isset($subpages['manager'])) {
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
