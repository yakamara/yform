<?php

use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\DataList;
use Redaxo\Core\View\Fragment;
use Redaxo\Core\View\Message;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Manager;
use Yakamara\YForm\YForm;

use function Redaxo\Core\View\escape;

/** @var Manager $this */

$subfunc = Request::request('subfunc', 'string');
$datasetId = Request::request('data_id', 'int', null);
$filterDataset = Request::request('filter_dataset', 'bool');
$historyId = Request::request('history_id', 'int');
$_csrf_key ??= '';

$historySearchId = Request::request('historySearchId', 'int', null);
$historySearchDate = Request::request('historySearchDate', 'string', null);
$historySearchUser = Request::request('historySearchUser', 'string', null);
$historySearchAction = Request::request('historySearchAction', 'string', null);

$dataset = null;
if ($datasetId) {
    $dataset = Dataset::getRaw($datasetId, $this->table->getTableName());
} else {
    $filterDataset = false;
}

$filterWhere = '';
if ($filterDataset) {
    echo Message::info('<b>' . I18n::msg('yform_history_dataset_id') . ':</b> ' . $datasetId);
    $filterWhere = ' AND dataset_id = ' . $datasetId;
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
    $filterWhere .= ' AND timestamp <= ' . Sql::factory()->escape($historyDateObject->format('Y-m-d'));
}

if ($historySearchUser) {
    $filterWhere .= ' AND user =' . Sql::factory()->escape($historySearchUser);
}

if ($historySearchAction) {
    $filterWhere .= ' AND action =' . Sql::factory()->escape($historySearchAction);
}

if ('view' === $subfunc && $dataset && $historyId) {
    $sql = Sql::factory();
    $timestamp = (string) $sql->setQuery(sprintf('SELECT `timestamp` FROM %s WHERE id = %d', Core::getTable('yform_history'), $historyId))->getValue('timestamp');

    $data = $sql->getArray(sprintf('SELECT * FROM %s WHERE history_id = %d', Core::getTable('yform_history_field'), $historyId));
    $data = array_column($data, 'value', 'field');

    $rows = '';

    foreach ($this->table->getValueFields() as $field) {
        if (!array_key_exists($field->getName(), $data)) {
            continue;
        }

        $value = $data[$field->getName()];
        $class = \Yakamara\YForm\FieldRegistry::getClass('value', $field->getTypeName());
        if (null !== $class && method_exists($class, 'getListValue')) {
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
            $value = escape($value);
        }

        $rows .= '
            <tr>
                <th class="rex-table-width-5">' . $field->getLabel() . '</th>
                <td>' . $value . '</td>
            </tr>';
    }

    $content = '
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title">
                ' . I18n::msg('yform_history_dataset') . ' ' . $datasetId . '
                <small>[' . date('d.m.Y H:i:s', strtotime($timestamp)) . ']</small>
            </h4>
        </div>
        <div class="modal-body">
            <table class="table">
                <tbody>
                    ' . $rows . '
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <a href="index.php?page=yform/manager/data_edit&amp;table_name=' . $this->table->getTableName() . '&amp;func=history&amp;subfunc=restore&amp;filter_dataset=' . ((int) $filterDataset) . '&amp;data_id=' . $datasetId . '&amp;history_id=' . $historyId . '&amp;' . http_build_query(CsrfToken::factory($_csrf_key)->getUrlParams()) . '" class="btn btn-warning">' . I18n::msg('yform_history_restore_this') . '</a>
            <button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true">&times;</button>
        </div>
    ';

    Response::sendContent($content);
    exit;
}

if ('restore' === $subfunc && $dataset && $historyId) {
    if ($dataset->restoreSnapshot($historyId)) {
        echo Message::success(I18n::msg('yform_history_restore_success'));
    } else {
        $error = '<ul>';
        foreach ($dataset->getMessages() as $msg) {
            $error .= '<li>' . I18n::translate($msg) . '</li>';
        }
        $error .= '</ul>';

        echo Message::error(I18n::msg('yform_history_restore_error') . '<br/>' . $error);
    }
}

if (Core::getUser()->admin && in_array($subfunc, ['delete_old', 'delete_all'], true)) {
    $where = $filterWhere;
    if ('delete_old' === $subfunc) {
        $where = ' AND h.`timestamp` < DATE_SUB(NOW(), INTERVAL 3 MONTH)';
    }

    $sql = Sql::factory();
    $sql->setQuery(
        sprintf('
            DELETE h, hf
            FROM %s h
            LEFT JOIN %s hf ON hf.history_id = h.id
            WHERE h.table_name = ? %s
        ', Core::getTable('yform_history'), Core::getTable('yform_history_field'), $where),
        [$this->table->getTableName()],
    );

    echo Message::success(I18n::msg('yform_history_delete_success'));
}

$sql = Sql::factory();

$listQuery = 'SELECT
        h.id as hid, dataset_id,
        id as title,
        `action`, `user`, `timestamp`
    FROM ' . Core::getTable('yform_history') . ' h
    WHERE
        `table_name` = ' . $sql->escape($this->table->getTableName()) .
    $filterWhere;

$userQuery = 'SELECT
        distinct `user`
    FROM ' . Core::getTable('yform_history') . ' h
    WHERE
        `table_name` = ' . $sql->escape($this->table->getTableName());

$list = DataList::factory($listQuery, defaultSort: [
    'hid' => 'desc',
    'timestamp' => 'desc',
]);

$users = $sql->getArray($userQuery);
$users = array_combine(array_column($users, 'user'), array_column($users, 'user'));

$list->addParam('table_name', $this->table->getTableName());
$list->addParam('func', 'history');
$list->addParam('_csrf_token', CsrfToken::factory($_csrf_key)->getValue());

if ($filterDataset) {
    $list->addParam('filter_dataset', 1);
    $list->addParam('data_id', $datasetId);
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

$list->setColumnLabel('dataset_id', I18n::msg('yform_history_dataset_id'));
$list->setColumnLabel('title', I18n::msg('yform_history_dataset'));
$list->setColumnFormat('title', 'custom', static function (array $params) {
    $result = Sql::factory()->getArray('select * from ' . Core::getTable('yform_history_field') . ' where history_id=:history_id and field IN ("title", "titel", "name", "last_name") LIMIT 1', [
        'history_id' => $params['value'],
    ]);
    $title = '[no title found]';
    if (isset($result[0])) {
        $title = $result[0]['value'];
        if (mb_strlen($title) > 50) {
            $title = substr($title, 0, 50) . '…';
        }
    }
    return escape($title);
});

$list->setColumnLabel('action', I18n::msg('yform_history_action'));
$list->setColumnFormat('action', 'custom', static function (array $params) {
    static $classes = [
        Dataset::ACTION_CREATE => 'success',
        Dataset::ACTION_UPDATE => 'primary',
        Dataset::ACTION_DELETE => 'danger',
    ];
    $class = $classes[$params['subject']] ?? 'default';
    return sprintf('<span class="label label-%s">%s</span>', $class, I18n::msg('yform_history_action_' . $params['subject']));
});

$list->setColumnLabel('user', I18n::msg('yform_history_user'));

$list->setColumnLabel('timestamp', I18n::msg('yform_history_timestamp'));
$list->setColumnFormat('timestamp', 'custom', static function (array $params) {
    return (new DateTime($params['subject']))->format('d.m.Y H:i:s');
});

$list->addColumn('view', '<i class="rex-icon fa-eye"></i> ' . I18n::msg('yform_history_view'), -1, ['<th></th>', '<td class="rex-table-action">###VALUE###</td>']);
$list->setColumnParams('view', ['subfunc' => 'view', 'data_id' => '###dataset_id###', 'history_id' => '###hid###']);
$list->addLinkAttribute('view', 'data-toggle', 'modal');
$list->addLinkAttribute('view', 'data-target', '#rex-yform-history-modal');

$list->addColumn('restore', '<i class="rex-icon fa-undo"></i> ' . I18n::msg('yform_history_restore'), -1, ['<th></th>', '<td class="rex-table-action">###VALUE###</td>']);
$list->setColumnParams('restore', ['subfunc' => 'restore', 'data_id' => '###dataset_id###', 'history_id' => '###hid###'] + CsrfToken::factory($_csrf_key)->getUrlParams());

$content = $list->get();

$options = '';

if (Core::getUser()->admin) {
    $buttons = [];

    $item = [];
    $item['label'] = I18n::msg('yform_history_delete_older_3_months');
    $item['url'] = $list->getUrl(['subfunc' => 'delete_old'] + CsrfToken::factory($_csrf_key)->getUrlParams());
    $item['attributes']['class'][] = 'btn-delete';
    $item['attributes']['onclick'][] = 'return confirm(\'' . I18n::msg('yform_history_delete_confirm') . '\');';
    $buttons[] = $item;

    $item = [];
    $item['label'] = I18n::msg('yform_history_delete_all');
    $item['url'] = $list->getUrl(['subfunc' => 'delete_all'] + CsrfToken::factory($_csrf_key)->getUrlParams());
    $item['attributes']['class'][] = 'btn-delete';
    $item['attributes']['onclick'][] = 'return confirm(\'' . I18n::msg('yform_history_delete_confirm') . '\');';
    $buttons[] = $item;

    $fragment = new Fragment();
    $fragment->setVar('size', 'xs', false);
    $fragment->setVar('buttons', $buttons, false);
    $options = '<small class="rex-panel-option-title">' . I18n::msg('yform_history_delete') . ':</small> ' . $fragment->parse('core/buttons/button_group.php');
}

$historySearchForm = new YForm();
$historySearchForm->setObjectparams('form_action', $list->getUrl());
$historySearchForm->setObjectparams('form_showformafterupdate', true);
$historySearchForm->setObjectparams('real_field_names', true);
$historySearchForm->setObjectparams('csrf_protection', false);
$historySearchForm->setHiddenField('_csrf_token', CsrfToken::factory($_csrf_key)->getValue());

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
    'notice' => I18n::msg('yform_manager_history_date_notice'),
    'attributes' => '{"data-yform-tools-datepicker":"YYYY-MM-DD"}',
]);
$historySearchForm->setValueField('choice', [
    'name' => 'historySearchAction',
    'label' => 'Action',
    'choices' => [
        '' => I18n::msg('yform_manager_actions_all'),
        Dataset::ACTION_CREATE => I18n::msg('yform_history_action_' . Dataset::ACTION_CREATE),
        Dataset::ACTION_UPDATE => I18n::msg('yform_history_action_' . Dataset::ACTION_UPDATE),
        Dataset::ACTION_DELETE => I18n::msg('yform_history_action_' . Dataset::ACTION_DELETE),
    ],
]);

$historySearchForm->setValueField('choice', [
    'name' => 'historySearchUser',
    'label' => 'User',
    'choices' => array_merge(
        ['' => I18n::msg('yform_manager_users_all')],
        $users,
    ),
]);

$fragment = new Fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', I18n::msg('yform_manager_search'));
$fragment->setVar('body', $historySearchForm->getForm(), false);
$searchForm = $fragment->parse('core/page/section.php');

$fragment = new Fragment();
$fragment->setVar('title', I18n::msg('yform_history'));
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
