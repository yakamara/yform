<?php

/**
 * REX_YFORM_TABLE_DATA[table="tablename"],.
 *
 * @package redaxo\yform\manager
 */
class rex_var_yform_table_data extends rex_var
{
    protected function getOutput()
    {
        $id = (int) $this->getArg('id', 0, true);
        $tableName = $this->getArg('table', '', true);
        $fieldName = $this->getArg('field', 'id', true);
        $output = $this->getArg('output', 'value', true);

        if (!in_array($this->getContext(), ['module'])) {
            return self::quote('[context ?]');
        } elseif (!is_numeric($id) || $id < 1 || $id > 20) {
            return self::quote('[id ?]');
        }

        $value = $this->getContextData()->getValue('value' . $id);

        if ($this->hasArg('isset') && $this->getArg('isset')) {
            return $value ? 'true' : 'false';
        }

        switch ($output) {
            case 'listwidget':
                if (!$this->environmentIs(self::ENV_INPUT)) {
                    return false;
                }

                $table = rex_yform_manager_table::get($tableName);
                if (!$table) {
                    return self::quote('[table not in YForm?]');
                }
                $tableName = $table->getTableName();

                $options = [];
                $valueArray = explode(',', $value);
                $values = [];
                if ($value != '') {
                    foreach ($valueArray as $valueID) {
                        $listValues = rex_yform_value_be_manager_relation::getListValues($table->getTableName(), $fieldName, ['id' => $valueID]);
                        if (isset($listValues[$valueID])) {
                            $name =  $listValues[$valueID];
                            if (strlen($name) > 50) {
                                $name = mb_substr($name, 0, 45) . ' ... ';
                            }

                            $options[] = ['id' => $valueID, 'name' => $name];
                            $values[] = $valueID;
                        }
                    }
                }
                $value = implode(',', $values);

                $args = [];
                $args['link'] = 'index.php?page=yform/manager/data_edit&table_name=' . $tableName;
                $args['table'] = $table;
                $args['fieldName'] = $fieldName;
                $args['options'] = $options;

                $value = self::getListWidget($id, 'REX_INPUT_VALUE[' . $id . ']', $value, $args);
                break;

            case 'widget':

                if (!$this->environmentIs(self::ENV_INPUT)) {
                    return false;
                }

                $table = rex_yform_manager_table::get($tableName);
                if (!$table) {
                    return self::quote('[table not in YForm?]');
                }
                $tableName = $table->getTableName();
                $valueName = '';

                $listValues = rex_yform_value_be_manager_relation::getListValues($table->getTableName(), $fieldName, ['id' => $value]);
                if (isset($listValues[$value])) {
                    $valueName =  $listValues[$value];
                    if (strlen($valueName) > 50) {
                        $valueName = mb_substr($valueName, 0, 45) . ' ... ';
                    }
                }

                $args = [];
                $args['link'] = 'index.php?page=yform/manager/data_edit&table_name=' . $tableName;
                $args['table'] = $table;
                $args['fieldName'] = $fieldName;
                $args['valueName'] = $valueName;

                $value = self::getWidget($id, 'REX_INPUT_VALUE[' . $id . ']', $value, $args);
                break;

            default:
                break;
        }

        return self::quote($value);
    }

    public static function getListWidget($id, $name, $value, array $args = [])
    {
        $link = $args['link'];

        $options = [];
        foreach ($args['options'] as $option) {
            $options[] = '<option value="' . $option['id'] . '">' .htmlspecialchars($option['name']) . ' [id=' . $option['id'] . ']</option>';
        }

        $e = [];
        $e['field'] = '
                <select class="form-control" name="yform_MANAGER_DATALIST_SELECT_' . $id . '" id="yform_MANAGER_DATALIST_SELECT_' . $id . '" size="10">
                    ' . implode('', $options) . '
                </select>
                <input type="hidden" name="' . $name . '" id="yform_MANAGER_DATALIST_' . $id . '" value="' . htmlspecialchars($value) . '" />';

        $e['moveButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $id . ',\'top\');return false;" title="' . rex_i18n::msg('yform_relation_move_first_data') . '"><i class="rex-icon rex-icon-top"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $id . ',\'up\');return false;" title="' . rex_i18n::msg('yform_relation_move_up_data') . '>"><i class="rex-icon rex-icon-up"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $id . ',\'down\');return false;" title="' . rex_i18n::msg('yform_relation_down_first_data') . '"><i class="rex-icon rex-icon-down"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $id . ',\'bottom\');return false;" title="' . rex_i18n::msg('yform_relation_move_last_data') . '"><i class="rex-icon rex-icon-bottom"></i></a>';
        $e['functionButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_openDatalist(' . $id . ', \'' . urlencode($args['fieldName']) . '\', \'' . $link . '\',\'1\');return false;" title="' . rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_deleteDatalist(' . $id . ',\'1\');return false;" title="' . rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>
            ';

        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget_list.php');
    }

    public static function getWidget($id, $name, $value, array $args = [])
    {
        $link = $args['link'];
        if ($value == "") {
            $valueName = '';

        } else {
            $valueName = htmlspecialchars($args['valueName']) . ' [id=' . $value . ']';

        }

        $e['field'] = '<input class="form-control" type="text" name="yform_MANAGER_DATANAME[' . $id . ']" value="' .  $valueName . '" id="yform_MANAGER_DATANAME_' . $id . '" readonly="readonly" /><input type="hidden" name="' .  $name . '" id="yform_MANAGER_DATA_' . $id . '" value="' . $value . '" />';
        $e['functionButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_openDatalist(' . $id . ', \'' . urlencode($args['fieldName']) . '\', \'' . $link . '\',\'0\');return false;" title="' .  rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_deleteDatalist(' . $id . ',\'0\');return false;" title="' .  rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>';

        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget.php');

    }
}
