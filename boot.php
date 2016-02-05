<?php

rex_yform::addTemplatePath(rex_path::addon('yform', 'ytemplates'));

if (rex::isBackend() && rex::getUser()) {
    rex_view::addCssFile($this->getAssetsUrl('yform.css'));
    rex_view::addCssFile($this->getAssetsUrl('yform-formbuilder.css'));
}
