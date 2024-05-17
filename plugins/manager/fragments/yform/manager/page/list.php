<?php

/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

$query = $this->getVar('query');
$table = $this->getVar('table');
$rex_link_vars = $this->getVar('rex_link_vars');
$rex_yform_manager_opener = $this->getVar('rex_yform_manager_opener');
$rex_yform_manager_popup = $this->getVar('rex_yform_manager_popup');
$popup = $this->getVar('popup');
$hasDataPageFunctions = $this->getVar('hasDataPageFunctions');

/** @var rex_yform_list $list */
$list = rex_yform_list::factory($query, $table->getListAmount());

$list->addTableAttribute('class', 'table-striped table-hover yform-table-' . rex_string::normalize($this->table->getTableName()));

$rex_yform_list[$list->getPager()->getCursorName()] = rex_request($list->getPager()->getCursorName(), 'int', 0);

if ($hasDataPageFunctions('add') && $this->table->isGranted('EDIT', rex::getUser())) {
    $thIcon = '<a class="rex-link-expanded" href="index.php?' . http_build_query(array_merge(['func' => 'add'], $rex_link_vars)) . '"' . rex::getAccesskey(rex_i18n::msg('add'), 'add') . '><i class="rex-icon rex-icon-add"></i></a>';
    $tdIcon = '<i class="rex-icon rex-icon-editmode"></i>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon" data-title="' . rex_i18n::msg('id') . '">###VALUE###</td>']);
    $list->setColumnParams($thIcon, array_merge(['data_id' => '###id###', 'func' => 'edit'], $rex_yform_list));
} else {
    $thIcon = '_';
    $tdIcon = '<i class="rex-icon rex-icon-view"></i>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon" data-title="' . rex_i18n::msg('id') . '">###VALUE###</td>']);
    $list->setColumnParams($thIcon, array_merge(['data_id' => '###id###', 'func' => 'edit'], $rex_yform_list));
}

$list->setColumnLabel('id', rex_i18n::msg('yform_id'));
$list->setColumnSortable('id');

// $link_list_params = array_merge(
//     $this->getLinkVars(),
//     ['table_name' => $this->table->getTablename()],
//     ['rex_yform_filter' => $rex_yform_filter],
//     ['rex_yform_set' => $rex_yform_set],
//     ['rex_yform_manager_opener' => $rex_yform_manager_opener],
//     ['rex_yform_manager_popup' => $rex_yform_manager_popup]
// );

$link_list_params = array_merge(
    $rex_link_vars,
    ['table_name' => $this->table->getTablename()],
);

$recArray = static function ($key, $paramsArray) use ($list, &$recArray) {
    if (!is_array($paramsArray)) {
        $list->addParam($key, $paramsArray);
    } elseif (is_array($paramsArray)) {
        foreach ($paramsArray as $k => $v) {
            $recArray($key . '[' . $k . ']', $v);
        }
    }
};
foreach ($link_list_params as $mainKey => $link_list_param) {
    $recArray($mainKey, $link_list_param);
}

foreach ($this->table->getFields() as $field) {
    if (!$field->isHiddenInList() && $field->getTypeName()) {
        if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getListValue')) {
            $list->setColumnFormat(
                $field->getName(),
                'custom',
                ['rex_yform_value_' . $field->getTypeName(), 'getListValue'],
                ['field' => $field->toArray(), 'fields' => $this->table->getFields()],
            );
        }
        $list->setColumnLayout($field->getName(), ['<th>###VALUE###</th>', '<td class="yform-table-field-value-' . rex_escape($field->getTypeName()) . ' yform-table-field-name-' . rex_escape($field->getName()) . '" data-title="###LABEL###">###VALUE###</td>']);
    }

    if ('value' == $field->getType()) {
        if ($field->isHiddenInList()) {
            $list->removeColumn($field->getName());
        } else {
            $list->setColumnSortable($field->getName());
            $list->setColumnLabel($field->getName(), $field->getLabel());
        }
    }
}

$colspan = 1;
if (isset($rex_yform_manager_opener['id'])) {
    $list->addColumn(rex_i18n::msg('yform_function'), '');
    $list->setColumnFormat(
        rex_i18n::msg('yform_function'),
        'custom',
        static function ($params) {
            $value = '';

            $tablefield = explode('.', $params['params']['opener_field']);
            if (1 == count($tablefield)) {
                if (isset($params['list']->getParams()['table_name'])) {
                    $target_table = $params['list']->getParams()['table_name'];
                    $target_field = $tablefield[0];
                    $values = rex_yform_value_be_manager_relation::getListValues($target_table, $target_field);
                    $value = $values[$params['list']->getValue('id')];
                }
            } else {
                [$table_name, $field_name] = explode('.', $params['params']['opener_field']);
                $table = rex_yform_manager_table::get($table_name);
                if ($table) {
                    $fields = $table->getValueFields(['name' => $field_name]);
                    if (isset($fields[$field_name])) {
                        $target_table = $fields[$field_name]->getElement('table');
                        $target_field = $fields[$field_name]->getElement('field');

                        $values = rex_yform_value_be_manager_relation::getListValues(
                            $target_table,
                            $target_field,
                        );
                        $value = $values[$params['list']->getValue('id')];
                    }
                }
            }
            return '<span class="yform-dataset-widget"><a
                class="btn btn-popup yform-dataset-widget-set"
                data-id="###id###"
                data-opener_id="' . $params['params']['opener_id'] . '"
                data-opener_field="' . $params['params']['opener_field'] . '"
                data-value="' . rex_escape($value, 'html') . ' [id=###id###]"
                data-multiple="' . $params['params']['opener_multiple'] . '">' . rex_i18n::msg('yform_data_select') . '</a></span>';
        },
        [
            'opener_id' => $rex_yform_manager_opener['id'] ?? '0',
            'opener_field' => $rex_yform_manager_opener['field'] ?? '',
            'opener_multiple' => $rex_yform_manager_opener['multiple'] ?? '0',
        ],
    );
} else {
    $actionButtonParams = array_merge(
        $list->getParams(),
        $rex_yform_list,
        ['rex_yform_manager_opener' => $rex_yform_manager_opener],
        ['rex_yform_manager_popup' => $rex_yform_manager_popup],
    );

    $actionButtons = [];
    $actionButtonsLoaded = $this->getVar('actionButtons') ?? [];

    foreach ($actionButtonsLoaded as $actionButtonKey => $actionButton) {
        if (!is_array($actionButton)) {
            continue;
        }

        $url = null;
        if (isset($actionButton['url']) && is_string($actionButton['url'])) {
            $url = $actionButton['url'];
        }
        if (!$url) {
            try {
                $url = $list->getUrl(
                    array_merge(
                        $actionButtonParams,
                        ['data_id' => '___id___', 'func' => $actionButtonKey],
                        $actionButton['params'],
                    ),
                    false,
                );
            } catch (throwable $e) {
                $url = '';
            }
        }

        $attributes = [];
        if (isset($actionButton['attributes'])) {
            $attributes = $actionButton['attributes'];
        }
        $attributes['href'] = $url;

        $actionButtons[$actionButtonKey] = '<a ' . rex_string::buildAttributes($attributes) . '>' . $actionButton['content'] . '</a>';
    }

    $fragment = new rex_fragment();
    $fragment->setVar('buttons', $actionButtons, false);
    $buttons = $fragment->parse('yform/manager/action_buttons.php');

    $list->addColumn(rex_i18n::msg('yform_function') . ' ', $buttons);
}

$list->setColumnLayout(rex_i18n::msg('yform_function') . ' ', ['<th class="rex-table-action" colspan="' . $colspan . '">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);

$list = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_LIST', $list, ['table' => $this->table]));

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_tabledata_overview'));
$fragment->setVar('options', implode('', $this->getVar('panelOptions')), false);
$fragment->setVar('content', $list->get(), false);
echo $fragment->parse('core/page/section.php');
