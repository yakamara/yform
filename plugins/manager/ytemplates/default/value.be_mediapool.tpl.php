<div class="yform-element <?php echo $this->getHTMLClass() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="text <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>

    <div class="rex-widget">
        <div class="rex-widget-media">
            <p class="rex-widget-field">
                <input type="text" class="text <?php echo $this->getWarningClass() ?>" name="<?php echo $this->getFieldName() ?>" id="REX_MEDIA_<?php echo $counter ?>" readonly="readonly" value="<?php echo htmlspecialchars(stripslashes($this->getValue())) ?>" />
            </p>
            <p class="rex-widget-icons rex-widget-1col">
                <span class="rex-widget-column rex-widget-column-first">
                    <a href="#" class="rex-icon-file-open" onclick="openREXMedia(<?php echo $counter ?>,'');return false;" title="Medium auswählen"></a>
                    <a href="#" class="rex-icon-file-add" onclick="addREXMedia(<?php echo $counter ?>);return false;" title="Neues Medium hinzufügen"></a>
                    <a href="#" class="rex-icon-file-delete" onclick="deleteREXMedia(<?php echo $counter ?>);return false;" title="Ausgewähltes Medium löschen"></a>
                    <a href="#" class="rex-icon-file-view" onclick="viewREXMedia(<?php echo $counter ?>);return false;" title="Medium auswählen"></a>
                </span>
            </p>
            <div class="rex-media-preview"></div>
        </div>
    </div>
    <div class="rex-clearer"></div>
</div>
