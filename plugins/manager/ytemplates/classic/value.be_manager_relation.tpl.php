
<?php if ($this->relation['relation_type'] < 2): ?>

    <p class="<?php echo $this->getHTMLClass() ?> formlabel-<?php echo $this->getName() ?>" id="<?php echo $this->getHTMLId() ?>">
        <label class="select <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabelStyle($this->relation['label']) ?></label>
        <select class="select" id="<?php echo $this->getFieldId() ?>" <?php echo $this->relation['relation_type'] == 1 ? 'name="' . $this->getFieldName() . '[]" multiple="multiple" size="' . $this->relation['size'] . '"' : 'name="' . $this->getFieldName() . '" size="1"', $this->relation['disabled'] ? ' disabled="disabled"' : '' ?>>
            <?php foreach ($options as $key => $value): ?>
                <option value="<?php echo $key ?>"<?php echo in_array($key, $this->getValue()) ? ' selected="selected"' : '' ?>><?php echo $value ?></option>
            <?php endforeach ?>
        </select>
    </p>

<?php else: ?>

    <div class="<?php echo $this->getHTMLClass() ?> formlabel-<?php echo $this->getName() ?>" id="<?php echo $this->getHTMLId() ?>">
        <label class="select <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabelStyle($this->relation['label']) ?></label>
        <div class="rex-widget">

            <?php if ($this->relation['relation_type'] == 4) {
    ?>

                <div class="rex-widget-data">
                    <p class="rex-widget-field">
                        <input type="hidden" name="<?php echo $this->getFieldName() ?>" id="YFORM_DATASET_<?php echo $this->getId() ?>" value="<?php echo implode(',', $this->getValue()) ?>" />
                        <?php

                        if ($this->params['main_id'] > 0) {
                            ?><a href="javascript:void(0);" onclick="newPoolWindow('<?php echo $link ?>');return false;"><?php echo rex_i18n::msg('yform_relation_edit_relations'); ?></a>
                            <?php
                        } else {
                            echo rex_i18n::msg('yform_relation_first_create_data');
                        } ?>

                    </p>
                </div>

            <?php
} elseif ($this->relation['relation_type'] == 2) {
                            ?>

                <div class="rex-widget-data">

                    <p class="rex-widget-field">
                        <input type="hidden" name="<?php echo $this->getFieldName() ?>" id="YFORM_DATASET_<?php echo $this->getId() ?>" value="<?php echo implode(',', $this->getValue()) ?>" />
                        <input type="text" size="30" name="YFORM_DATASET_NAME[<?php echo $this->getId() ?>]" value="<?php echo htmlspecialchars($valueName) ?>" id="YFORM_DATASET_<?php echo $this->getId() ?>_NAME" readonly="readonly" class="text" />
                    </p>
                    <p class="rex-widget-icons rex-widget-1col">
                    <span class="rex-widget-column rex-widget-column-first">
                        <a href="javascript:void(0);" class="rex-icon-file-open" onclick="openYFormDataset(<?php echo $this->getId() ?>, '<?php echo $this->relation['source_table'].'.'.$this->getName(); ?>', '<?php echo $link ?>','0');return false;" title="<?php echo rex_i18n::msg('yform_relation_choose_entry') ?>"></a>
                        <a href="javascript:void(0);" class="rex-icon-file-delete" onclick="deleteYFormDataset(<?php echo $this->getId() ?>,'0');return false;" title="<?php echo rex_i18n::msg('yform_relation_delete_entry') ?>"></a>
                    </span>
                    </p>
                </div>

            <?php
                        } else {
                            ?>

                <div class="rex-widget-yform-manager-datalist">
                    <input type="hidden" name="<?php echo $this->getFieldName() ?>" id="YFORM_DATASETLIST_<?php echo $this->getId() ?>" value="<?php echo implode(',', $this->getValue()) ?>" />
                    <p class="rex-widget-field">
                        <select name="YFORM_DATASETLIST_SELECT_<?php echo $this->getId() ?>" id="YFORM_DATASETLIST_SELECT_<?php echo $this->getId() ?>" size="<?php echo $this->relation['size'] ?>">';
                            <?php foreach ($options as $key => $value): ?>
                                <option value="<?php echo $key ?>"><?php echo $value ?></option>
                            <?php endforeach ?>
                        </select>
                    </p>
                    <p class="rex-widget-icons rex-widget-2col">
                        <span class="rex-widget-column rex-widget-column-first">
                            <a href="javascript:void(0);" class="rex-icon-file-top" onclick="moveYFormDatasetList(<?php echo $this->getId() ?>,'top');return false;" title="<?php echo rex_i18n::msg('yform_relation_move_first_data') ?>"></a>
                            <a href="javascript:void(0);" class="rex-icon-file-up" onclick="moveYFormDatasetList(<?php echo $this->getId() ?>,'up');return false;" title="<?php echo rex_i18n::msg('yform_relation_move_up_data') ?>"></a>
                            <a href="javascript:void(0);" class="rex-icon-file-down" onclick="moveYFormDatasetList(<?php echo $this->getId() ?>,'down');return false;" title="<?php echo rex_i18n::msg('yform_relation_down_first_data') ?>"></a>
                            <a href="javascript:void(0);" class="rex-icon-file-bottom" onclick="moveYFormDatasetList(<?php echo $this->getId() ?>,'bottom');return false;" title="<?php echo rex_i18n::msg('yform_relation_move_last_data') ?>"></a>
                        </span>
                        <span class="rex-widget-column">
                            <a href="javascript:void(0);" class="rex-icon-file-open" onclick="openYFormDatasetList(<?php echo $this->getId() ?>, '<?php echo $this->relation['source_table'].'.'.$this->getName() ?>', '<?php echo $link ?>','1');return false;" title="<?php echo rex_i18n::msg('yform_relation_choose_entry') ?>"></a>
                            <a href="javascript:void(0);" class="rex-icon-file-delete" onclick="deleteYFormDatasetList(<?php echo $this->getId() ?>,'1');return false;" title="<?php echo rex_i18n::msg('yform_relation_delete_entry') ?>"></a>
                        </span>
                    </p>
                </div>

            <?php
                        } ?>

        </div>
        <div class="rex-clearer"></div>
    </div>

<?php endif ?>
