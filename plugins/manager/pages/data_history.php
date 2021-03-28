<?php

/**
 * @var rex_yform_manager $this
 */

$subfunc = rex_request('subfunc', 'string');
$datasetId = rex_request('dataset_id', 'int');
$filterDataset = rex_request('filter_dataset', 'bool');
$historyId = rex_request('history_id', 'int');

$dataset = null;
if ($datasetId) {
    $dataset = rex_yform_manager_dataset::getRaw($datasetId, $this->table->getTableName());
} else {
    $filterDataset = false;
}

$filterWhere = '';
if ($filterDataset) {
    echo rex_view::info('<b>' . rex_i18n::msg('yform_history_dataset_id') .':</b> ' . $datasetId);

    $filterWhere = ' AND dataset_id = '.$datasetId;
}

if ('view' === $subfunc && $dataset && $historyId) {
    $sql = rex_sql::factory();
    $timestamp = $sql->setQuery(sprintf('SELECT `timestamp` FROM %s WHERE id = %d', rex::getTable('yform_history'), $historyId))->getValue('timestamp');

    $data = $sql->getArray(sprintf('SELECT * FROM %s WHERE history_id = %d', rex::getTable('yform_history_field'), $historyId));
    $data = array_column($data, 'value', 'field');

    $rows = '';

    foreach ($this->table->getValueFields() as $field) {
        if (!array_key_exists($field->getName(), $data)) {
            continue;
        }

        $value = $data[$field->getName()];
        $class = 'rex_yform_value_'.$field->getTypeName();
        if (method_exists($class, 'getListValue')) {
            $value = $class::getListValue([
                'value' => $value,
                'subject' => $value,
                'params' => [
                    'field' => $field->toArray(),
                    'fields' => $this->table->getFields(),
                ],
            ]);
        } else {
            $value = htmlspecialchars($value);
        }

        $rows .= '
            <tr>
                <th class="rex-table-width-5">'.$field->getLabel().'</th>
                <td>'.$value.'</td>
            </tr>';
    }

    $content = '
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title">
                '.rex_i18n::msg('yform_history_dataset').' '.$datasetId.'
                <small>['.date('d.m.Y H:i:s', strtotime($timestamp)).']</small>
            </h4>
        </div>
        <div class="modal-body">
            <table class="table">
                <tbody>
                    '.$rows.'
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <a href="index.php?page=yform/manager/data_edit&amp;table_name='.$this->table->getTableName().'&amp;func=history&amp;subfunc=restore&amp;filter_dataset='.((int) $filterDataset).'&amp;dataset_id='.$datasetId.'&amp;history_id='.$historyId.'&amp;'.http_build_query(rex_csrf_token::factory($_csrf_key)->getUrlParams()).'" class="btn btn-warning">'.rex_i18n::msg('yform_history_restore_this').'</a>
            <button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true">&times;</button>
        </div>
    ';

    rex_response::sendContent($content);
    exit;
}

if ('restore' === $subfunc && $dataset && $historyId) {
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
    $where = $filterWhere;
    if ('delete_old' === $subfunc) {
        $where = ' AND h.`timestamp` < DATE_SUB(NOW(), INTERVAL 3 MONTH)';
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
    LEFT JOIN '.rex::getTable('yform_history_field').' hf ON hf.history_id = h.id AND hf.field IN ("title", "titel", "name", "last_name")
    WHERE `table_name` = '.$sql->escape($this->table->getTableName()).$filterWhere.'
    GROUP BY h.id
    ORDER BY `timestamp` DESC'
);

$list->addParam('table_name', $this->table->getTableName());
$list->addParam('func', 'history');

if ($filterDataset) {
    $list->addParam('filter_dataset', 1);
    $list->addParam('dataset_id', $datasetId);
}

$list->removeColumn('id');

$list->setColumnLabel('dataset_id', rex_i18n::msg('yform_history_dataset_id'));
$list->setColumnLabel('title', rex_i18n::msg('yform_history_dataset'));

$list->setColumnLabel('action', rex_i18n::msg('yform_history_action'));
$list->setColumnFormat('action', 'custom', static function (array $params) {
    static $classes = [
        rex_yform_manager_dataset::ACTION_CREATE => 'success',
        rex_yform_manager_dataset::ACTION_UPDATE => 'primary',
        rex_yform_manager_dataset::ACTION_DELETE => 'danger',
    ];
    $class = $classes[$params['subject']] ?? 'default';
    return sprintf('<span class="label label-%s">%s</span>', $class, rex_i18n::msg('yform_history_action_'.$params['subject']));
});

$list->setColumnLabel('user', rex_i18n::msg('yform_history_user'));

$list->setColumnLabel('timestamp', rex_i18n::msg('yform_history_timestamp'));
$list->setColumnFormat('timestamp', 'custom', static function (array $params) {
    return (new DateTime($params['subject']))->format('d.m.Y H:i:s');
});

$list->addColumn('view', '<i class="rex-icon fa-eye"></i> '.rex_i18n::msg('yform_history_view'), -1, ['<th></th>', '<td class="rex-table-action">###VALUE###</td>']);
$list->setColumnParams('view', ['subfunc' => 'view', 'dataset_id' => '###dataset_id###', 'history_id' => '###id###']);
$list->addLinkAttribute('view', 'data-toggle', 'modal');
$list->addLinkAttribute('view', 'data-target', '#rex-yform-history-modal');

$list->addColumn('restore', '<i class="rex-icon fa-undo"></i> '.rex_i18n::msg('yform_history_restore'), -1, ['<th></th>', '<td class="rex-table-action">###VALUE###</td>']);
$list->setColumnParams('restore', ['subfunc' => 'restore', 'dataset_id' => '###dataset_id###', 'history_id' => '###id###'] + rex_csrf_token::factory($_csrf_key)->getUrlParams());

$content = $list->get();

$options = '';

if (rex::getUser()->isAdmin()) {
    $buttons = [];

    $item = [];
    $item['label'] = rex_i18n::msg('yform_history_delete_older_3_months');
    $item['url'] = $list->getUrl(['subfunc' => 'delete_old'] + rex_csrf_token::factory($_csrf_key)->getUrlParams());
    $item['attributes']['class'][] = 'btn-delete';
    $item['attributes']['onclick'][] = 'return confirm(\'' . rex_i18n::msg('yform_history_delete_confirm') . '\');';
    $buttons[] = $item;

    $item = [];
    $item['label'] = rex_i18n::msg('yform_history_delete_all');
    $item['url'] = $list->getUrl(['subfunc' => 'delete_all'] + rex_csrf_token::factory($_csrf_key)->getUrlParams());
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

?>
<div class="modal fade" id="rex-yform-history-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        </div>
    </div>
</div>
