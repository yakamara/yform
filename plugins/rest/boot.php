<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @var rex_plugin $this
 */

rex_extension::register('PACKAGES_INCLUDED', function () {
    if (!\rex::isBackend()) {
        \rex_yform_rest::handleRoutes();
    }
});
