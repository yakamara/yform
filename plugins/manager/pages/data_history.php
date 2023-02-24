<?php

/**
 * @var rex_yform_manager $this
 */

$subfunc = rex_request('subfunc', 'string');
$datasetId = rex_request('dataset_id', 'int');
$filterDataset = rex_request('filter_dataset', 'bool');
$historyId = rex_request('history_id', 'int');
$_csrf_key = $_csrf_key ?? '';

$historySearchId = rex_request('historySearchId', 'int', null);
$historySearchDate = rex_request('historySearchDate', 'string', null);
$historySearchUser = rex_request('historySearchUser', 'string', null);
$historySearchAction = rex_request('historySearchAction', 'string', null);

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

if ($historySearchId) {
    $filterWhere .= ' AND dataset_id = ' . $historySearchId;
}

if ($historySearchDate) {
    $historyDateObject = DateTime::createFromFormat('Y-m-d', $historySearchDate);
    if (!$historyDateObject) {
        $historyDateObject = new DateTime();
    }
    $historyDateObject->modify('+1 day');
    $filterWhere .= ' AND timestamp <= ' . rex_sql::factory()->escape($historyDateObject->format('Y-m-d'));
}

if ($historySearchUser) {
    $filterWhere .= ' AND user =' . rex_sql::factory()->escape($historySearchUser);
}

if ($historySearchAction) {
    $filterWhere .= ' AND action =' . rex_sql::factory()->escape($historySearchAction);
}

if ('view' === $subfunc && $dataset && $historyId) {
    $sql = rex_sql::factory();
    $timestamp = (string) $sql->setQuery(sprintf('SELECT `timestamp` FROM %s WHERE id = %d', rex::getTable('yform_history'), $historyId))->getValue('timestamp');

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
                'field' => $field->getName(),
                'params' => [
                    'field' => $field->toArray(),
                    'fields' => $this->table->getFields(),
                ],
            ]);
        } else {
            $value = rex_escape($value);
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

$listQuery = 'SELECT
        h.id, dataset_id,
        id as title,
        `action`, `user`, `timestamp`
    FROM '.rex::getTable('yform_history').' h
    WHERE
        `table_name` = '.$sql->escape($this->table->getTableName()).
    $filterWhere.'
    GROUP BY h.id
    ORDER BY `timestamp` DESC';

$userQuery = 'SELECT
        distinct `user`
    FROM '.rex::getTable('yform_history').' h
    WHERE
        `table_name` = '.$sql->escape($this->table->getTableName());

$list = rex_list::factory($listQuery);

$users = $sql->getArray($userQuery);
$users = array_combine(array_column($users, 'user'), array_column($users, 'user'));

$list->addParam('table_name', $this->table->getTableName());
$list->addParam('func', 'history');
$list->addParam('_csrf_token', rex_csrf_token::factory($_csrf_key)->getValue());

if ($filterDataset) {
    $list->addParam('filter_dataset', 1);
    $list->addParam('dataset_id', $datasetId);
}

if ($historySearchId) {
    $list->addParam('historySearchId', $historySearchId);
}

if ($historySearchDate) {
    $list->addParam('historySearchDate', $historySearchDate);
}

if ($historySearchUser) {
    $list->addParam('historySearchUser', $historySearchUser);
}

if ($historySearchAction) {
    $list->addParam('historySearchAction', $historySearchAction);
}

$list->removeColumn('id');

$list->setColumnLabel('dataset_id', rex_i18n::msg('yform_history_dataset_id'));
$list->setColumnLabel('title', rex_i18n::msg('yform_history_dataset'));
$list->setColumnFormat('title', 'custom', static function (array $params) {
    $result = rex_sql::factory()->getArray('select * from '.rex::getTable('yform_history_field').' where history_id=:history_id and field IN ("title", "titel", "name", "last_name") LIMIT 1', [
        'history_id' => $params['value'],
    ]);
    $title = '[no title found]';
    if (isset($result[0])) {
        $title = $result[0]['value'];
        if (mb_strlen($title) > 50) {
            $title = substr($title, 0, 50). '…';
        }
    }
    return rex_escape($title);
});

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

$historySearchForm = new rex_yform();
$historySearchForm->setObjectparams('form_action', $list->getUrl());
$historySearchForm->setObjectparams('form_showformafterupdate', true);
$historySearchForm->setObjectparams('real_field_names', true);
$historySearchForm->setObjectparams('csrf_protection', false);
$historySearchForm->setHiddenField('_csrf_token', rex_csrf_token::factory($_csrf_key)->getValue());

if (!$datasetId) {
    $historySearchForm->setValueField('text', [
        'name' => 'historySearchId',
        'label' => 'id',
    ]);
}

$historySearchForm->setValueField('date', [
    'name' => 'historySearchDate',
    'label' => 'Date',
    'widget' => 'input:text',
    'current_date' => true,
    'notice' => rex_i18n::msg('yform_manager_history_date_notice'),
    'attributes' => '{"data-yform-tools-datepicker":"YYYY-MM-DD"}',
]);
$historySearchForm->setValueField('choice', [
    'name' => 'historySearchAction',
    'label' => 'Action',
    'choices' => [
        '' => rex_i18n::msg('yform_manager_actions_all'),
        rex_yform_manager_dataset::ACTION_CREATE => rex_i18n::msg('yform_history_action_'.rex_yform_manager_dataset::ACTION_CREATE),
        rex_yform_manager_dataset::ACTION_UPDATE => rex_i18n::msg('yform_history_action_'.rex_yform_manager_dataset::ACTION_UPDATE),
        rex_yform_manager_dataset::ACTION_DELETE => rex_i18n::msg('yform_history_action_'.rex_yform_manager_dataset::ACTION_DELETE),
    ],
]);

$historySearchForm->setValueField('choice', [
    'name' => 'historySearchUser',
    'label' => 'User',
    'choices' => array_merge(
        ['' => rex_i18n::msg('yform_manager_users_all')],
        $users
    ),
]);

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('yform_manager_search'));
$fragment->setVar('body', $historySearchForm->getForm(), false);
$searchForm = $fragment->parse('core/page/section.php');

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_history'));
$fragment->setVar('options', $options, false);
$fragment->setVar('content', $content, false);
$searchList = $fragment->parse('core/page/section.php');

echo '<div class="row">';
echo '<div class="col-sm-3 col-md-3 col-lg-2">' . $searchForm . '</div>';
echo '<div class="col-sm-9 col-md-9 col-lg-10">' . $searchList . '</div>';
echo '</div>';

?>
<div class="modal fade" id="rex-yform-history-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        </div>
    </div>
</div>
