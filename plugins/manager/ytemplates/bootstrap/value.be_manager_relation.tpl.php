<?php
$class_group = trim('form-group yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
?>
<?php if ($this->relation['relation_type'] < 2): ?>
    <div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
        <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabelStyle($this->relation['label']) ?></label>
        <?php
        $select = new rex_select();
        $select->setStyle('class="form-control"');
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
        $select->addOptions($options);
        $select->setSelected($this->getValue());
        echo $select->get();
        ?>
    </div>
<?php else: ?>
    <div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
        <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabelStyle($this->relation['label']) ?></label>
        <?php
        $e = [];
        if ($this->relation['relation_type'] == 4) {
            $e['field'] = '<input type="hidden" name="' . $this->getFieldName() . '" id="yform_MANAGER_DATA_' . $this->getId() . '" value="' . implode(',', $this->getValue()) . '" />';
            if ($this->params["main_id"] > 0) {
                $e['functionButtons'] = '<a class="btn btn-popup" href="javascript:void(0);" onclick="newPoolWindow(\'' . $link . '\');return false;">' . rex_i18n::msg('yform_relation_edit_relations') . '</a>';
            } else {
                $e['after'] = '<p class="help-block">' . rex_i18n::msg('yform_relation_first_create_data') . '</p>';
            }

            $fragment = new rex_fragment();
            $fragment->setVar('elements', [$e], false);
            echo $fragment->parse('core/form/widget.php');

        } else if ($this->relation['relation_type'] == 2) {
            $e['field'] = '<input class="form-control" type="text" name="yform_MANAGER_DATANAME[' . $this->getId() . ']" value="' .  htmlspecialchars($valueName) . '" id="yform_MANAGER_DATANAME_' . $this->getId() . '" readonly="readonly" /><input type="hidden" name="' .  $this->getFieldName() . '" id="yform_MANAGER_DATA_' . $this->getId() . '" value="' . implode(',', $this->getValue()) . '" />';
            $e['functionButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_openDatalist(' . $this->getId() . ', \'' . $this->relation['source_table'] . '.' . $this->getName() . '\', \'' . $link . '\',\'0\');return false;" title="' .  rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_deleteDatalist(' . $this->getId() . ',\'0\');return false;" title="' .  rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>';

            $fragment = new rex_fragment();
            $fragment->setVar('elements', [$e], false);
            echo $fragment->parse('core/form/widget.php');

        } else {
            $select = new rex_select();
            $select->setStyle('class="form-control"');
            $select->setId('yform_MANAGER_DATALIST_SELECT_' . $this->getId(). '');
            $select->setName('yform_MANAGER_DATALIST_SELECT' . $this->getId(). '');
            $select->setSize($this->relation['size']);
            $select->addOptions($options);
            $e['field'] = $select->get() . '<input type="hidden" name="' . $this->getFieldName() . '" id="yform_MANAGER_DATALIST_' . $this->getId() . '" value="' . implode(',', $this->getValue()) . '" />';

            $e['moveButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $this->getId() . ',\'top\');return false;" title="' . rex_i18n::msg('yform_relation_move_first_data') . '"><i class="rex-icon rex-icon-top"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $this->getId() . ',\'up\');return false;" title="' . rex_i18n::msg('yform_relation_move_up_data') . '>"><i class="rex-icon rex-icon-up"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $this->getId() . ',\'down\');return false;" title="' . rex_i18n::msg('yform_relation_down_first_data') . '"><i class="rex-icon rex-icon-down"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_moveDatalist(' . $this->getId() . ',\'bottom\');return false;" title="' . rex_i18n::msg('yform_relation_move_last_data') . '"><i class="rex-icon rex-icon-bottom"></i></a>';
            $e['functionButtons'] = '
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_openDatalist(' . $this->getId() . ', \'' . $this->relation['source_table'].' . '.$this->getName() . '\', \'' . $link . '\',\'1\');return false;" title="' . rex_i18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a href="javascript:void(0);" class="btn btn-popup" onclick="yform_manager_deleteDatalist(' . $this->getId() . ',\'1\');return false;" title="' . rex_i18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>
            ';

            $fragment = new rex_fragment();
            $fragment->setVar('elements', [$e], false);
            echo $fragment->parse('core/form/widget_list.php');
        }
        ?>
    </div>
<?php endif ?>
