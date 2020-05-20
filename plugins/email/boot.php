<?php

if (rex::isBackend() && rex::getUser()) {
    if (rex_addon::get('watson')->isAvailable()) {
        function yfe_search(rex_extension_point $ep)
        {
            $subject = $ep->getSubject();
            $subject[] = 'Watson\Workflows\YForm\YFormEmailTemplateProvider';
            return $subject;
        }
        rex_extension::register('WATSON_PROVIDER', 'yfe_search', rex_extension::LATE);
    }
}
