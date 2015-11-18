<?php
$class_group = trim('form-group yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
?>

<div class="<?= $class_group ?>">
    <label for="<?= $this->getFieldId() ?>"><?= $this->getLabel() ?></label>
    <input type="file" id="<?= $this->getFieldId() ?>" name="file_<?= md5($this->getFieldName('file')) ?>" />
    <?php if ($this->getValue()): ?>
        <div class="help-block">
            <dl class="<?= $this->getHTMLClass() ?>-info">
                <dt>Dateiname</dt>
                <dd><a href="files/<?php echo htmlspecialchars($this->getValue()) ?>"><?php echo htmlspecialchars($this->getValue()) ?></a></dd>
                <?php if (in_array(substr(strtolower($this->getValue()), -4), array('.jpg', '.png', '.gif'))): ?>
                    <dd><img class="img-responsive" src="?rex_img_type=profileimage&amp;rex_img_file=<?php echo htmlspecialchars($this->getValue()) ?>" /></dd>
                <?php endif ?>
            </dl>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="<?php echo md5($this->getFieldName('delete')) ?>" value="1" />
                    Datei löschen
                </label>
            </div>
        </div>
    <?php endif ?>
    <input type="hidden" name="<?php echo $this->getFieldName() ?>" value="<?php echo htmlspecialchars($this->getValue()) ?>" />
</div>
