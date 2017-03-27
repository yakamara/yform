<?php

$class_group = trim('form-group yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$id = crc32($this->params['form_name']) . rand(0, 10000) . $this->getId();

$notice = array();
if ($this->getElement('notice') != '') {
    $notice[] = rex_i18n::translate($this->getElement('notice'));
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] =  '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';

} else {
    $notice = '';
}

?>
<?php if ($this->relation['relation_type'] < 2): ?>
    <div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
        <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabelStyle($this->relation['label']) ?></label>
        <?php

        $attributes = [];
        $attributes['class'] = 'form-control';
        $attributes['id'] = $this->getFieldId();

        $select = new rex_select();

        if ($this->relation['relation_type'] == 1) {
            $select->setName($this->getFieldName() . '[]');
            $select->setMultiple();
            $select->setSize($this->relation['size']);

        } else {
            $select->setName($this->getFieldName());

        }

        if ($this->relation['disabled']) {
            $select->setAttribute('disabled', 'disabled');

        }

        $attributes = $this->getAttributeArray($attributes, ['required', 'readonly']);

        $select->setAttributes($attributes);
        foreach ($options as $option) {
            $select->addOption($option['name'], $option['id']);
        }

        $select->setSelected($this->getValue());
        echo $select->get();
        ?>
        <?php echo $notice ?>
    </div>
<?php else: ?>
    <div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
        <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabelStyle($this->relation['label']) ?></label>
        <?php
        $e = [];
        if ($this->relation['relation_type'] == 4) {
            $e['field'] = '<input type="hidden" name="' . $this->getFieldName() . '" id="yform_MANAGER_DATA_' . $id . '" value="' . implode(',', $this->getValue()) . '" />';
            if ($this->params['main_id'] > 0) {
                $e['functionButtons'] = '<a class="btn btn-popup" href="javascript:void(0);" onclick="newPoolWindow(\'' . $link . '\');return false;">' . rex_i18n::msg('yform_relation_edit_relations') . '</a>';
            } else {
                $e['after'] = '<p class="help-block">' . rex_i18n::msg('yform_relation_first_create_data') . '</p>';
            }

            $fragment = new rex_fragment();
            $fragment->setVar('elements', [$e], false);
            echo $fragment->parse('core/form/widget.php');

        } elseif ($this->relation['relation_type'] == 2) {
            $e['field'] = '<input class="form-control" type="text" name="yform_MANAGER_DATANAME[' . $id . ']" value="' .  htmlspecialchars($valueName) . '" id="yform_MANAGER_DATANAME_' . $id . '" readonly="readonly" /><input type="hidden" name="' .  $this->getFieldName() . '" id="yform_MANAGER_DATA_' . $id . '" value="' . implode(',', $this->getValue()) . '" />';
            $e['functionButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_openDatalist(' . $id . ', \'' . $this->relation['source_table'] . '.' . $this->getName() . '\', \'' . $link . '\',\'0\');return false;" title="' .  rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_deleteDatalist(' . $id . ',\'0\');return false;" title="' .  rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>';

            $fragment = new rex_fragment();
            $fragment->setVar('elements', [$e], false);
            echo $fragment->parse('core/form/widget.php');

        } else {

            $attributes = [];
            $attributes['class'] = 'form-control';
            $attributes = $this->getAttributeArray($attributes, ['required', 'readonly']);

            $select = new rex_select();
            $select->setAttributes($attributes);
            $select->setId('yform_MANAGER_DATALIST_SELECT_' . $id . '');
            $select->setName('yform_MANAGER_DATALIST_SELECT' . $id . '');
            $select->setSize($this->relation['size']);
            foreach ($options as $option) {
                $select->addOption($option['name'], $option['id']);
            }
            $e['field'] = $select->get() . '<input type="hidden" name="' . $this->getFieldName() . '" id="yform_MANAGER_DATALIST_' . $id . '" value="' . implode(',', $this->getValue()) . '" />';

            $e['moveButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $id . ',\'top\');return false;" title="' . rex_i18n::msg('yform_relation_move_first_data') . '"><i class="rex-icon rex-icon-top"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $id . ',\'up\');return false;" title="' . rex_i18n::msg('yform_relation_move_up_data') . '>"><i class="rex-icon rex-icon-up"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $id . ',\'down\');return false;" title="' . rex_i18n::msg('yform_relation_down_first_data') . '"><i class="rex-icon rex-icon-down"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $id . ',\'bottom\');return false;" title="' . rex_i18n::msg('yform_relation_move_last_data') . '"><i class="rex-icon rex-icon-bottom"></i></a>';
            $e['functionButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_openDatalist(' . $id . ', \'' . $this->relation['source_table'] . '.' . $this->getName() . '\', \'' . $link . '\',\'1\');return false;" title="' . rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_deleteDatalist(' . $id . ',\'1\');return false;" title="' . rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>
            ';

            $fragment = new rex_fragment();
            $fragment->setVar('elements', [$e], false);
            echo $fragment->parse('core/form/widget_list.php');
        }
        ?>
        <?php echo $notice ?>
    </div>
<?php endif;
