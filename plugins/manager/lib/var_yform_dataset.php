<?php

/**
 * REX_YFORM_TABLE_DATA[id=1 table="tablename"],.
 * REX_YFORM_TABLE_DATA[id=1 table="tablename" widget=1],.
 * REX_YFORM_TABLE_DATA[id=1 table="tablename" field=name widget=1],.
 * REX_YFORM_TABLE_DATA[id=1 table="tablename" field=name widget=1 multiple=1],.
 *
 * @package redaxo\yform\manager
 */
class rex_var_yform_table_data extends rex_var
{
    protected function getOutput()
    {
        $id = $this->getArg('id', 0, true);
        if (!in_array($this->getContext(), ['module', 'action']) || !is_numeric($id) || $id < 1 || $id > 20) {
            return false;
        }

        $tableName = $this->getArg('table', '', true);
        $fieldName = $this->getArg('field', 'id', true);

        $value = $this->getContextData()->getValue('value' . $id);

        if ($this->hasArg('isset') && $this->getArg('isset')) {
            return $value ? 'true' : 'false';
        }

        if ($this->hasArg('widget') && $this->getArg('widget')) {
            if (!$this->environmentIs(self::ENV_INPUT)) {
                return false;
            }

            if ($tableName == '') {
                return self::quote('[table param not defined]');
            }

            $table = rex_yform_manager_table::get($tableName);
            if (!$table) {
                return self::quote('[table not in YForm?]');
            }

            $args = [
                'link' => 'index.php?page=yform/manager/data_edit&table_name=' . $table->getTableName(),
                'table' => $table,
                'fieldName' => $fieldName,
            ];

            if ($this->hasArg('multiple') && $this->getArg('multiple')) {
                $options = [];
                $values = [];
                if ($value != '') {
                    $valueArray = explode(',', $value);
                    foreach ($valueArray as $valueId) {
                        $listValues = rex_yform_value_be_manager_relation::getListValues($table->getTableName(), $fieldName, ['id' => $valueId]);
                        if (isset($listValues[$valueId])) {
                            $options[] = ['id' => $valueId, 'name' => rex_formatter::truncate($listValues[$valueId], ['length' => 50])];
                            $values[] = $valueId;
                        }
                    }
                }
                $args['options'] = $options;
                $value = implode(',', $values);

                $value = self::getMultipleWidget($id, 'REX_INPUT_VALUE[' . $id . ']', $value, $args);

            } else {
                $valueName = '';
                $listValues = rex_yform_value_be_manager_relation::getListValues($table->getTableName(), $fieldName, ['id' => $value]);
                if (isset($listValues[$value])) {
                    $valueName = rex_formatter::truncate($listValues[$value], ['length' => 50]);
                }
                $args['valueName'] = $valueName;
                $value = self::getSingleWidget($id, 'REX_INPUT_VALUE[' . $id . ']', $value, $args);
            }
        }
        //else {
        //    if ($value && $this->hasArg('output') && $this->getArg('output') != 'id') {
        //        if ($tableName == '') {
        //            return self::quote('[table param not defined]');
        //        }
        //
        //        $table = rex_yform_manager_table::get($tableName);
        //        if (!$table) {
        //            return self::quote('[table not in YForm?]');
        //        }
        //
        //        $query = rex_yform_manager_dataset::query($table->getTableName());
        //        $method = (strpos($value, ',') === false) ? 'findId' : 'findIDs';
        //        $value = $query->{$method}($value);
        //        //return 'rex_var::nothing(require rex_stream::factory(substr(__FILE__, 6) . \'/REX_YFORM_DATASET/'.$id.'\', '.self::quote(json_encode($value)).'))';
        //        return self::quote(json_encode($value));
        //    }
        //}
        return self::quote($value);
    }

    public static function getMultipleWidget($id, $name, $value, array $args = [])
    {
        $link = $args['link'];

        $options = [];
        foreach ($args['options'] as $option) {
            $options[] = '<option value="' . $option['id'] . '">' .rex_escape(trim(sprintf('%s [%s]', $option['name'], $option['id']))).'</option>';
        }

        $e = [];
        $e['field'] = '
                <select class="form-control" name="YFORM_DATASETLIST_SELECT_' . $id . '" id="YFORM_DATASETLIST_SELECT_' . $id . '" size="10">
                    ' . implode('', $options) . '
                </select>
                <input type="hidden" name="' . $name . '" id="YFORM_DATASETLIST_' . $id . '" value="' . rex_escape(($value)) . '" />';

        $e['moveButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="moveYFormDatasetList(' . $id . ',\'top\');return false;" title="' . rex_i18n::msg('yform_relation_move_first_data') . '"><i class="rex-icon rex-icon-top"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="moveYFormDatasetList(' . $id . ',\'up\');return false;" title="' . rex_i18n::msg('yform_relation_move_up_data') . '>"><i class="rex-icon rex-icon-up"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="moveYFormDatasetList(' . $id . ',\'down\');return false;" title="' . rex_i18n::msg('yform_relation_down_first_data') . '"><i class="rex-icon rex-icon-down"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="moveYFormDatasetList(' . $id . ',\'bottom\');return false;" title="' . rex_i18n::msg('yform_relation_move_last_data') . '"><i class="rex-icon rex-icon-bottom"></i></a>';
        $e['functionButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="openYFormDatasetList(' . $id . ', \'' . urlencode($args['fieldName']) . '\', \'' . $link . '\');return false;" title="' . rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="deleteYFormDatasetList(' . $id . ');return false;" title="' . rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>
            ';

        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget_list.php');
    }

    public static function getSingleWidget($id, $name, $value, array $args = [])
    {
        $link = $args['link'];
        $valueName = '';
        if ($value != '') {
            $valueName = rex_escape(trim(sprintf('%s [%s]', $args['valueName'], $value)));
        }

        $e['field'] = '<input class="form-control" type="text" name="YFORM_DATASET_NAME[' . $id . ']" value="' .  $valueName . '" id="YFORM_DATASET_' . $id . '_NAME" readonly="readonly" /><input type="hidden" name="' .  $name . '" id="YFORM_DATASET_' . $id . '" value="' . $value . '" />';
        $e['functionButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="openYFormDataset(' . $id . ', \'' . urlencode($args['fieldName']) . '\', \'' . $link . '\');return false;" title="' .  rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="deleteYFormDataset(' . $id . ');return false;" title="' .  rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>';

        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget.php');
    }
}
