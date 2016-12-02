<?php

/**
 * @var rex_yform_manager $this
 */

$subfunc = rex_request('subfunc', 'string');
$datasetId = rex_request('dataset_id', 'int');
$historyId = rex_request('history_id', 'int');

if ('restore' === $subfunc && $datasetId && $historyId) {
    $dataset = rex_yform_manager_dataset::getRaw($datasetId, $this->table->getTableName());

    if ($dataset->restoreSnapshot($historyId)) {
        echo rex_view::success(rex_i18n::msg('yform_history_restore_success'));
    } else {
        $error = '<ul>';
        foreach ($dataset->getMessages() as $msg) {
            $error .= '<li>'.rex_i18n::translate($msg).'</li>';
        }
        $error .= '</ul>';

        echo rex_view::error(rex_i18n::msg('yform_history_restore_error').'<br/>'.$error);
    }
}

if (rex::getUser()->isAdmin() && in_array($subfunc, ['delete_old', 'delete_all'], true)) {
    $where = '';
    if ('delete_old' === $subfunc) {
        $where = 'AND h.`timestamp` < DATE_SUB(NOW(), INTERVAL 3 MONTH)';
    }

    $sql = rex_sql::factory();
    $sql->setQuery(
        sprintf('
            DELETE h, hf
            FROM %s h
            LEFT JOIN %s hf ON hf.history_id = h.id
            WHERE h.table_name = ? %s
        ', rex::getTable('yform_history'), rex::getTable('yform_history_field'), $where),
        [$this->table->getTableName()]
    );

    echo rex_view::success(rex_i18n::msg('yform_history_delete_success'));
}

$sql = rex_sql::factory();
$list = rex_list::factory(
    'SELECT
        h.id, dataset_id,
        IF(LENGTH(hf.value) > 50, CONCAT(LEFT(hf.value, 50), "…"), hf.value) as title,
        `action`, `user`, `timestamp`
    FROM '.rex::getTable('yform_history').' h
    LEFT JOIN '.rex::getTable('yform_history_field').' hf ON hf.history_id = h.id AND hf.field IN ("title", "name", "last_name")
    WHERE `table_name` = '.$sql->escape($this->table->getTableName()).'
    GROUP BY h.id
    ORDER BY `timestamp` DESC'
);

$list->addParam('table_name', $this->table->getTableName());
$list->addParam('func', 'history');

$list->removeColumn('id');

$list->setColumnLabel('dataset_id', rex_i18n::msg('yform_history_dataset_id'));
$list->setColumnLabel('title', rex_i18n::msg('yform_history_dataset'));

$list->setColumnLabel('action', rex_i18n::msg('yform_history_action'));
$list->setColumnFormat('action', 'custom', function (array $params) {
    static $classes = [
        rex_yform_manager_dataset::ACTION_CREATE => 'success',
        rex_yform_manager_dataset::ACTION_UPDATE => 'primary',
        rex_yform_manager_dataset::ACTION_DELETE => 'danger',
    ];
    $class = isset($classes[$params['subject']]) ? $classes[$params['subject']] : 'default';
    return sprintf('<span class="label label-%s">%s</span>', $class, rex_i18n::msg('yform_history_action_'.$params['subject']));
});

$list->setColumnLabel('user', rex_i18n::msg('yform_history_user'));

$list->setColumnLabel('timestamp', rex_i18n::msg('yform_history_timestamp'));
$list->setColumnFormat('timestamp', 'custom', function (array $params) {
    return (new DateTime($params['subject']))->format('d.m.Y H:i:s');
});

$list->addColumn('restore', rex_i18n::msg('yform_history_restore'), -1, ['<th></th>', '<td class="rex-table-action">###VALUE###</td>']);
$list->setColumnParams('restore', ['subfunc' => 'restore', 'dataset_id' => '###dataset_id###', 'history_id' => '###id###']);

$content = $list->get();

$options = '';

if (rex::getUser()->isAdmin()) {
    $buttons = [];

    $item = [];
    $item['label'] = rex_i18n::msg('yform_history_delete_older_3_months');
    $item['url'] = $list->getUrl(['subfunc' => 'delete_old']);
    $item['attributes']['class'][] = 'btn-delete';
    $item['attributes']['onclick'][] = 'return confirm(\'' . rex_i18n::msg('yform_history_delete_confirm') . '\');';
    $buttons[] = $item;

    $item = [];
    $item['label'] = rex_i18n::msg('yform_history_delete_all');
    $item['url'] = $list->getUrl(['subfunc' => 'delete_all']);
    $item['attributes']['class'][] = 'btn-delete';
    $item['attributes']['onclick'][] = 'return confirm(\'' . rex_i18n::msg('yform_history_delete_confirm') . '\');';
    $buttons[] = $item;

    $fragment = new rex_fragment();
    $fragment->setVar('size', 'xs', false);
    $fragment->setVar('buttons', $buttons, false);
    $options = '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_history_delete') . ':</small> ' . $fragment->parse('core/buttons/button_group.php');
}

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_history'));
$fragment->setVar('options', $options, false);
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');
