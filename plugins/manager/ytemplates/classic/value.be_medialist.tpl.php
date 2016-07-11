<div class="yform-element formbe_medialist <?php echo $this->getHTMLClass() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="text <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>
    <div class="rex-widget">
        <div class="rex-widget-medialist<?php echo $this->getElement(3) ? ' rex-widget-preview rex-widget-preview-image-manager' : '' ?>">
            <input type="hidden" name="<?php echo $this->getFieldName() ?>" id="REX_MEDIALIST_<?php echo $counter ?>" value="<?php echo htmlspecialchars($this->getValue()) ?>" />
            <p class="rex-widget-field">
                <select name="MEDIALIST_SELECT[<?php echo $counter ?>]" id="REX_MEDIALIST_SELECT_<?php echo $counter ?>" size="8">
                    <?php foreach ($medialist as $value): ?>
                        <option value="<?php echo $value ?>"><?php echo $value ?></option>
                    <?php endforeach ?>
                </select>
            </p>

            <p class="rex-widget-icons rex-widget-2col">
                <span class="rex-widget-column rex-widget-column-first">
                    <a href="#" class="rex-icon-file-top" onclick="moveREXMedialist(<?php echo $counter ?>,'top');return false;" title="<?php echo rex_i18n::msg('var_medialist_move_top') ?>"></a>
                    <a href="#" class="rex-icon-file-up" onclick="moveREXMedialist(<?php echo $counter ?>,'up');return false;" title="<?php echo rex_i18n::msg('var_medialist_move_up') ?>"></a>
                    <a href="#" class="rex-icon-file-down" onclick="moveREXMedialist(<?php echo $counter ?>,'down');return false;" title="<?php echo rex_i18n::msg('var_medialist_move_down') ?>"></a>
                    <a href="#" class="rex-icon-file-bottom" onclick="moveREXMedialist(<?php echo $counter ?>,'bottom');return false;" title="<?php echo rex_i18n::msg('var_medialist_move_bottom') ?>"></a>
                </span>
                <span class="rex-widget-column">
                    <a href="#" class="rex-icon-file-open" onclick="openREXMedialist(<?php echo $counter ?>, '<?php echo $args ?>');return false;" title="<?php echo rex_i18n::msg('var_media_open') ?>"></a>
                    <a href="#" class="rex-icon-file-add" onclick="addREXMedialist(<?php echo $counter ?>);return false;" title="<?php echo rex_i18n::msg('var_media_new') ?>"></a>
                    <a href="#" class="rex-icon-file-delete" onclick="deleteREXMedialist(<?php echo $counter ?>);return false;" title="<?php echo rex_i18n::msg('var_media_remove') ?>"></a>
                    <a href="#" class="rex-icon-file-view" onclick="viewREXMedialist(<?php echo $counter ?>);return false;" title="<?php echo rex_i18n::msg('var_media_open') ?>"></a>
                </span>
            </p>
            <div class="rex-media-preview"></div>
        </div>
    </div>
    <div class="rex-clearer"></div>
</div>
