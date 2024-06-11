<?php

/** @var rex_yform_manager $this */

$subfunc = rex_request('subfunc', 'string');
$datasetId = rex_request('data_id', 'int', 0);
$filterDataset = rex_request('filter_dataset', 'bool');
$historyId = rex_request('history_id', 'int');
$_csrf_key ??= '';

$historySearchId = rex_request('historySearchId', 'int', 0);
$historySearchDate = trim(rex_request('historySearchDate', 'string', ''));
$historySearchUser = trim(rex_request('historySearchUser', 'string', ''));
$historySearchAction = trim(rex_request('historySearchAction', 'string', ''));

$table = $this->table;

$dataset = null;
$filterWhere = '';

if ($datasetId > 0) {
    $dataset = rex_yform_manager_dataset::getRaw($datasetId, $table->getTableName());

    if ($filterDataset) {
        // echo rex_view::info('<b>' . rex_i18n::msg('yform_history_dataset_id') . ':</b> ' . $datasetId);
        $filterWhere = ' AND dataset_id = ' . $datasetId;
    }
} else {
    $filterDataset = false;
}

// detailed dataset history
$isDatasetHistory = null !== $dataset && $dataset->exists() && $datasetId > 0;

if ($historySearchId > 0 && !$isDatasetHistory) {
    $filterWhere .= ' AND dataset_id = ' . $historySearchId;
}

if ('' !== $historySearchDate) {
    $historyDateObject = DateTime::createFromFormat('Y-m-d', $historySearchDate);

    if (!$historyDateObject) {
        $historyDateObject = new DateTime();
    }

    $historyDateObject->modify('+1 day');
    $filterWhere .= ' AND timestamp < ' . rex_sql::factory()->escape($historyDateObject->format('Y-m-d'));
}

if ('' !== $historySearchUser) {
    $filterWhere .= ' AND user =' . rex_sql::factory()->escape($historySearchUser);
}

if ('' !== $historySearchAction) {
    $filterWhere .= ' AND action =' . rex_sql::factory()->escape($historySearchAction);
}

if ('view' === $subfunc && $isDatasetHistory) {
    $historyDiff = new rex_fragment();
    $historyDiff->setVar('history_id', $historyId);
    $historyDiff->setVar('dataset_id', $datasetId);
    $historyDiff->setVar('current_dataset', $dataset);
    $historyDiff->setVar('table', $table, false);
    $historyDiff->setVar('csrf_key', $_csrf_key);

    rex_response::sendContent($historyDiff->parse('yform/manager/history.diff.php'));
    exit;
}

if ('restore' === $subfunc && $isDatasetHistory) {
    if ($dataset->restoreSnapshot($historyId)) {
        echo rex_view::success(rex_i18n::msg('yform_history_restore_success'));
    } else {
        $error = '<ul>';
        foreach ($dataset->getMessages() as $msg) {
            $error .= '<li>' . rex_i18n::translate($msg) . '</li>';
        }
        $error .= '</ul>';

        echo rex_view::error(rex_i18n::msg('yform_history_restore_error') . '<br/>' . $error);
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
        [$table->getTableName()],
    );

    echo rex_view::success(rex_i18n::msg('yform_history_delete_success'));
}

$sql = rex_sql::factory();

$listQuery = '
    SELECT
        h.id as hid,
        dataset_id,
        id as title,
        `action`, `user`, `timestamp`
    FROM ' . rex::getTable('yform_history') . ' h
    WHERE
        `table_name` = ' . $sql->escape($table->getTableName()) .
    $filterWhere;

$userQuery = '
    SELECT
        distinct `user`
    FROM ' . rex::getTable('yform_history') . ' h
    WHERE
        `table_name` = ' . $sql->escape($table->getTableName());

$list = rex_list::factory($listQuery, defaultSort: [
    'timestamp' => 'asc',
    'hid' => 'asc',
]);
$list->addFormAttribute('class', 'history-list');

$users = $sql->getArray($userQuery);
$users = array_combine(array_column($users, 'user'), array_column($users, 'user'));

$list->addParam('table_name', $table->getTableName());
$list->addParam('func', 'history');
$list->addParam('_csrf_token', rex_csrf_token::factory($_csrf_key)->getValue());

if ($filterDataset) {
    $list->addParam('filter_dataset', 1);

    if (null !== $dataset && $dataset->exists() && $datasetId > 0) {
        $list->addParam('data_id', $datasetId);
    }
}

if ($historySearchId > 0) {
    $list->addParam('historySearchId', $historySearchId);
}

if ('' !== $historySearchDate) {
    $list->addParam('historySearchDate', $historySearchDate);
}

if ('' !== $historySearchUser) {
    $list->addParam('historySearchUser', $historySearchUser);
}

if ('' !== $historySearchAction) {
    $list->addParam('historySearchAction', $historySearchAction);
}

$list->removeColumn('id');

$list->setColumnLabel('hid', rex_i18n::msg('yform_id'));
$list->setColumnLabel('dataset_id', rex_i18n::msg('yform_history_dataset_id'));
$list->setColumnLabel('title', rex_i18n::msg('yform_history_dataset'));
$list->setColumnFormat('title', 'custom', static function (array $params) {
    $result = rex_sql::factory()->getArray('select * from ' . rex::getTable('yform_history_field') . ' where history_id=:history_id and field IN ("title", "titel", "name", "last_name") LIMIT 1', [
        'history_id' => $params['value'],
    ]);
    $title = '[no title found]';
    if (isset($result[0])) {
        $title = $result[0]['value'];
        if (mb_strlen($title) > 50) {
            $title = substr($title, 0, 50) . '…';
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
    return sprintf('<span class="label label-%s">%s</span>', $class, rex_i18n::msg('yform_history_action_' . $params['subject']));
});

$list->setColumnLabel('user', rex_i18n::msg('yform_history_user'));

$list->setColumnLabel('timestamp', rex_i18n::msg('yform_history_timestamp'));
$list->setColumnFormat('timestamp', 'custom', static function (array $params) {
    return (new DateTime($params['subject']))->format('d.m.Y H:i:s');
});

$viewColumnBody = '<i class="rex-icon fa-eye"></i> ' . rex_i18n::msg('yform_history_view');
$restoreColumnBody = '<i class="rex-icon fa-undo"></i> ' . rex_i18n::msg('yform_history_restore');
$actionsCell = '<td class="rex-table-action">###VALUE###</td>';
$normalCell = '<td>###VALUE###</td>';

if ($isDatasetHistory) {
    // dataset column already in header, so not necessary to have it in the list
    $list->removeColumn('dataset_id');
    $list->addColumn('view', $viewColumnBody, -1, ['<th></th>', $actionsCell]);

    $list->setColumnParams('view', ['subfunc' => 'view', 'data_id' => '###dataset_id###', 'history_id' => '###hid###']);
    $list->addLinkAttribute('view', 'data-toggle', 'modal');
    $list->addLinkAttribute('view', 'data-target', '#rex-yform-history-modal');
}

$list->addColumn('restore', $restoreColumnBody, -1, ['<th></th>', $actionsCell]);
$list->setColumnParams('restore', ['subfunc' => 'restore', 'data_id' => '###dataset_id###', 'history_id' => '###hid###'] + rex_csrf_token::factory($_csrf_key)->getUrlParams());
$list->addLinkAttribute('restore', 'onclick', 'return confirm(\'' . rex_i18n::msg('yform_history_restore_confirm') . '\');');

$historyDatasets = [];
$sql = rex_sql::factory();

// when showing history of specific dataset
if ($isDatasetHistory) {
    // revision / number
    $revision = 'revision';
    $list->addColumn($revision, '', 2, [
        '<th>' . rex_i18n::msg('yform_history_revision') . '</th>',
        '<td class="special-states">###VALUE###</td>',
    ]);

    rex::setProperty('YFORM_HISTORY_REVISION', 0);

    $list->setColumnFormat(
        $revision,
        'custom',
        static function ($a) use (&$rev, &$historyDatasets, &$table, &$sql) {
            // early column ... store all values for current revision
            $rev = rex::getProperty('YFORM_HISTORY_REVISION', 0);

            $historyDatasets[$rev] = [
                'values' => [],
                'added' => [],
                'added_current' => [],
                'removed' => [],
                'removed_current' => [],
            ];

            $data = $sql->getArray(sprintf('SELECT * FROM %s WHERE history_id = :id', rex::getTable('yform_history_field')), [':id' => $a['list']->getValue('hid')]);
            $data = array_column($data, 'value', 'field');

            foreach ($table->getValueFields() as $field) {
                $class = 'rex_yform_value_' . $field->getTypeName();

                if (!array_key_exists($field->getName(), $data)) {
                    if (method_exists($class, 'getListValue')) {
                        $historyDatasets[$rev]['added'][$field->getName()] = $historyDatasets[$rev]['added_current'][$field->getName()] = true;
                    }

                    continue;
                }

                // set data
                $historyDatasets[$rev]['values'][$field->getName()] = $data[$field->getName()];
                unset($data[$field->getName()]);
            }

            // check for deleted fields in historic data
            foreach ($data as $field => $value) {
                $historyDatasets[$rev]['removed_current'][$field] = $value;
            }

            // compare with prev
            if (isset($historyDatasets[$rev - 1])) {
                $prev = &$historyDatasets[$rev - 1];

                // clean up added
                foreach ($historyDatasets[$rev]['added'] as $field => $true) {
                    if (isset($prev['added'][$field])) {
                        unset($prev['added'][$field]);
                    }
                }

                // handle removed
                foreach ($prev['removed_current'] as $field => $value) {
                    if (!isset($historyDatasets[$rev]['removed_current'][$field])) {
                        $historyDatasets[$rev]['removed'][$field] = $value;
                    }
                }
            }

            rex::setProperty('YFORM_HISTORY_REVISION', $rev + 1);
            // dump($historyDatasets);
            return $rev;
        },
    );

    // changes compared to current dataset
    $changesCurrent = 'changes_to_current';
    $list->addColumn($changesCurrent, '', 3, [
        '<th>' . rex_i18n::msg('yform_history_diff_to_current') . '</th>',
        '<td>###VALUE###</td>',
    ]);

    $viewColumnLayout = $list->getColumnLayout('view');

    $list->setColumnFormat(
        $changesCurrent,
        'custom',
        static function ($a) use (&$dataset, $table, &$historyDatasets, $actionsCell, $normalCell, $changesCurrent) {
            $rev = rex::getProperty('YFORM_HISTORY_REVISION', 0) - 1;

            $changes = 0;
            $added = count($historyDatasets[$rev]['added_current']);
            $removed = count($historyDatasets[$rev]['removed_current']);

            $historyDataset = &$historyDatasets[$rev]['values'];

            foreach ($table->getValueFields() as $field) {
                if (!array_key_exists($field->getName(), $historyDataset)) {
                    continue;
                }

                $historyValue = $historyDataset[$field->getName()];
                $currentValue = ($dataset->hasValue($field->getName()) ? $dataset->getValue($field->getName()) : '-');

                if ('' . $historyValue !== '' . $currentValue) {
                    ++$changes;
                }
            }

            // handle actions column
            if (0 === $changes) {
                $a['list']->setColumnLayout($changesCurrent, ['<th></th>', '<td class="current-dataset-row">###VALUE###</td>']);
                $a['list']->setColumnLayout('view', ['<th></th>', '<td colspan="2" class="current-dataset-cell"><span class="current-dataset-hint">' . rex_i18n::msg('yform_history_is_current_dataset') . '</span></td>']);
                $a['list']->setColumnLayout('restore', ['<th></th>', '']);
            } else {
                $a['list']->setColumnLayout($changesCurrent, ['<th></th>', $normalCell]);
                $a['list']->setColumnLayout('view', ['<th></th>', $actionsCell]);
                $a['list']->setColumnLayout('restore', ['<th></th>', $actionsCell]);
            }

            return $changes .
                ($added > 0 || $removed > 0 ?
                    ' (' . ($added > 0 ? '+' . $added . ' ' . rex_i18n::msg('yform_history_diff_added') : '') .
                    ($removed > 0 ? ($added > 0 ? ', ' : '') . '+' . $removed . ' ' . rex_i18n::msg('yform_history_diff_removed') : '')
                    . ')' : ''
                );
        },
    );

    // changes compared to previous dataset
    $changesPrev = 'changes_to_prev';
    $list->addColumn($changesPrev, '', 4, [
        '<th>' . rex_i18n::msg('yform_history_diff_to_previous') . '</th>',
        '<td>###VALUE###</td>',
    ]);

    $sql = rex_sql::factory();

    $list->setColumnFormat(
        $changesPrev,
        'custom',
        static function ($a) use (&$historyDatasets) {
            $rev = rex::getProperty('YFORM_HISTORY_REVISION', 0) - 1;

            $changes = 0;
            $added = (isset($historyDatasets[$rev - 1]) ? count($historyDatasets[$rev - 1]['added']) : 0);
            $removed = count($historyDatasets[$rev]['removed']);

            $historyDataset = &$historyDatasets[$rev]['values'];

            if (isset($historyDatasets[$rev - 1])) {
                $prevHistoryDataset = $historyDatasets[$rev - 1]['values'];

                foreach ($historyDataset as $field => $value) {
                    if ('' . $historyDataset[$field] !== '' . $prevHistoryDataset[$field]) {
                        ++$changes;
                        // dump($rev.": ".$historyDataset[$field]." - ".$prevHistoryDataset[$field]." - ".$changes);
                    }
                }
            }

            return $changes .
                ($added > 0 || $removed > 0 ?
                    ' (' . ($added > 0 ? '+' . $added . ' ' . rex_i18n::msg('yform_history_diff_added') : '') .
                    ($removed > 0 ? ($added > 0 ? ', ' : '') . '+' . $removed . ' ' . rex_i18n::msg('yform_history_diff_removed') : '')
                    . ')' : ''
                );
        },
    );
}

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

if (!$isDatasetHistory) {
    $historySearchForm->setValueField('text', [
        'name' => 'historySearchId',
        'label' => rex_i18n::msg('yform_id'),
    ]);
} else {
    $historySearchForm->setHiddenField('dataset_id', $datasetId);
}

$historySearchForm->setValueField('date', [
    'name' => 'historySearchDate',
    'label' => rex_i18n::msg('yform_history_timestamp'),
    'widget' => 'input:text',
    'current_date' => true,
    'notice' => rex_i18n::msg('yform_manager_history_date_notice'),
    'attributes' => '{"data-yform-tools-datepicker":"YYYY-MM-DD"}',
]);

$historySearchForm->setValueField('choice', [
    'name' => 'historySearchAction',
    'label' => rex_i18n::msg('yform_history_action'),
    'choices' => [
        '' => rex_i18n::msg('yform_manager_actions_all'),
        rex_yform_manager_dataset::ACTION_CREATE => rex_i18n::msg('yform_history_action_' . rex_yform_manager_dataset::ACTION_CREATE),
        rex_yform_manager_dataset::ACTION_UPDATE => rex_i18n::msg('yform_history_action_' . rex_yform_manager_dataset::ACTION_UPDATE),
        rex_yform_manager_dataset::ACTION_DELETE => rex_i18n::msg('yform_history_action_' . rex_yform_manager_dataset::ACTION_DELETE),
    ],
]);

$historySearchForm->setValueField('choice', [
    'name' => 'historySearchUser',
    'label' => rex_i18n::msg('yform_history_user'),
    'choices' => array_merge(
        ['' => rex_i18n::msg('yform_manager_users_all')],
        $users,
    ),
]);

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('yform_manager_search'));
$fragment->setVar('body', $historySearchForm->getForm(), false);
$searchForm = $fragment->parse('core/page/section.php');

$fragment = new rex_fragment();

if ($isDatasetHistory) {
    $fragment->setVar('title', rex_i18n::msg('yform_history_title') . ' <b>' . rex_i18n::msg('yform_history_dataset_id') . ': ' . $datasetId . '</b>', false);
} else {
    $fragment->setVar('title', rex_i18n::msg('yform_history'));
}

$fragment->setVar('options', $options, false);
$fragment->setVar('content', $content, false);
$searchList = $fragment->parse('core/page/section.php');

if ((null === $dataset || !$dataset->exists()) && $datasetId > 0) {
    echo rex_view::warning(rex_i18n::msg('yform_history_dataset_missing'));
}

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
