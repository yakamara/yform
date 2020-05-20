<?php

namespace Watson\Workflows\YFormEmailTemplate;

use Watson\Foundation\SupportProvider;
use Watson\Foundation\Workflow;

class YFormEmailTemplateProvider extends SupportProvider
{
    /**
     * Register the directory to search a translation file.
     *
     * @return string
     */
    public function i18n()
    {
        return __DIR__;
    }

    /**
     * Register the service provider.
     *
     * @return Workflow|array
     */
    public function register()
    {
        if (\rex_plugin::get('yform', 'email')->isAvailable()) {
            return $this->registerYFormEmailTemplateSearch();
        }
        return [];
    }

    /**
     * Register yform search.
     *
     * @return Workflow
     */
    public function registerYFormEmailTemplateSearch()
    {
        return new YFormEmailTemplateSearch();
    }
}
