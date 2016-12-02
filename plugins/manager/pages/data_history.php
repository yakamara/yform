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

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_history'));
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');
