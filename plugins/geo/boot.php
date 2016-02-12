<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @var rex_plugin $this
 */

rex_yform::addTemplatePath(rex_path::plugin('yform','geo','ytemplates'));

if (rex::isBackend() && rex::getUser()) {
    rex_view::addJsFile($this->getAssetsUrl('geo.js'));
    rex_view::addCssFile($this->getAssetsUrl('geo.css'));

}

