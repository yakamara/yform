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

            if ('' == $tableName) {
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
                if ('' != $value) {
                    $valueArray = explode(',', $value);
                    foreach ($valueArray as $valueId) {
                        $listValues = rex_yform_value_be_manager_relation::getListValues($table->getTableName(), $fieldName, ['id' => $valueId]);
                        if (isset($listValues[$valueId])) {
                            $options[] = ['id' => $valueId, 'name' => rex_formatter::truncate($listValues[$valueId].' id=['.$valueId.']', ['length' => 50])];
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
        //        //return 'rex_var::nothing(require rex_stream::factory(mb_substr(__FILE__, 6) . \'/REX_YFORM_DATASET/'.$id.'\', '.self::quote(json_encode($value)).'))';
        //        return self::quote(json_encode($value));
        //    }
        //}
        return self::quote($value);
    }

    public static function getMultipleWidget($id, $name, $value, array $args = [])
    {
        $link = $args['link'];
        $size = $args['size'] ?? 10;

        $attributes = [];
        $attributes['class'] = 'form-control yform-dataset-view';
        $attributes = array_merge($attributes, $args['attributes'] ?? []);

        $dataset_view_id = 'yform-dataset-view-' . $id . '';
        $dataset_real_id = 'yform-dataset-real-' . $id . '';

        $select = new rex_select();
        $select->setAttributes($attributes);
        $select->setId($dataset_view_id);
        $select->setName($dataset_view_id.'-name');
        $select->setSize($size);
        foreach ($args['options'] as $option) {
            $select->addOption($option['name'], $option['id']);
        }

        $e = [];
        $e['field'] = $select->get() . '
                <input type="hidden" class="yform-dataset-real" name="' . $name . '" id="' . $dataset_real_id . '" value="' . rex_escape($value) . '" />';

        $e['moveButtons'] = '
                <a class="btn btn-popup yform-dataset-widget-move yform-dataset-widget-move-top" title="' . rex_i18n::msg('yform_relation_move_first_data') . '"><i class="rex-icon rex-icon-top"></i></a>
                <a class="btn btn-popup yform-dataset-widget-move yform-dataset-widget-move-up" title="' . rex_i18n::msg('yform_relation_move_up_data') . '>"><i class="rex-icon rex-icon-up"></i></a>
                <a class="btn btn-popup yform-dataset-widget-move yform-dataset-widget-move-down" title="' . rex_i18n::msg('yform_relation_down_first_data') . '"><i class="rex-icon rex-icon-down"></i></a>
                <a class="btn btn-popup yform-dataset-widget-move yform-dataset-widget-move-bottom" title="' . rex_i18n::msg('yform_relation_move_last_data') . '"><i class="rex-icon rex-icon-bottom"></i></a>';
        $e['functionButtons'] = '
                <a class="btn btn-popup yform-dataset-widget-open" title="' . rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a class="btn btn-popup yform-dataset-widget-delete" title="' . rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>
            ';
        $e['before'] = '<div class="yform-dataset-widget"
            data-widget_type="multiple"
            data-id="'.$id.'"
            data-link="'.$link.'"
            data-field_name="'.urlencode($args['fieldName']).'">';
        $e['after'] = '</div>';

        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget_list.php');
    }

    public static function getSingleWidget($id, $name, $value, array $args = [])
    {
        $link = $args['link'];
        $valueName = '';
        if ('' != $value) {
            $valueName = rex_escape(trim(sprintf('%s [%s]', $args['valueName'], $value)));
        }
        $dataset_view_id = 'yform-dataset-view-' . $id . '';
        $dataset_real_id = 'yform-dataset-real-' . $id . '';

        $e['field'] = '
            <input class="form-control yform-dataset-view" type="text" value="' .  $valueName . '" id="' . $dataset_view_id . '" readonly="readonly" />
            <input type="hidden" class="yform-dataset-real" name="' .  $name . '" id="' . $dataset_real_id . '" value="' . $value . '" />';
        $e['functionButtons'] = '
                <a class="btn btn-popup yform-dataset-widget-open" title="' .  rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a class="btn btn-popup yform-dataset-widget-delete" title="' .  rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>';
        $e['before'] = '<div class="yform-dataset-widget"
            data-widget_type="single"
            data-id="'.$id.'"
            data-value_name="'.$valueName.'"
            data-value="'.$value.'"
            data-link="'.$link.'"
            data-field_name="'.urlencode($args['fieldName']).'">';
        $e['after'] = '</div>';

        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget.php');
    }

    public static function getRelationWidget($id, $fieldName, $value, $link, $main_id)
    {
        $e['field'] = '<input type="hidden" name="' . $fieldName . '" id="YFORM_DATASET_' . $id . '" value="' . implode(',', $value) . '" />';
        $e['before'] = '<div class="yform-dataset-widget"
            data-widget_type="pool"
            data-link="'.$link.'">';
        $e['after'] = '';
        if ($main_id > 0) {
            $e['functionButtons'] = '<a class="btn btn-popup yform-dataset-widget-pool">' . rex_i18n::msg('yform_relation_edit_relations') . '</a>';
        } else {
            $e['after'] = '<p class="help-block small">' . rex_i18n::msg('yform_relation_first_create_data') . '</p>';
        }
        $e['after'] .= '</div>';

        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget.php');
    }
}
