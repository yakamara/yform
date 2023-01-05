<?php

rex_extension::register('EDITOR_URL', static function (rex_extension_point $ep) {
    static $urls = [
        'yform' => ['templates', 'template_id'],
        'module' => ['modules/modules', 'module_id'],
        'action' => ['modules/actions', 'action_id'],
    ];

    if (preg_match('@^rex:///yform/email/template/(.*)/(.*)@', $ep->getParam('file'), $match)) {
        return rex_url::backendPage(
            'yform/email/index',
            [
                'func' => 'edit',
                'template_key' => $match[1],
            ]
        );
    }
    return $ep->getParam('file');
});
