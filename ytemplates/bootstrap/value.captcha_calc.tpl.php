<?php
$class_group = trim('form-group yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
?>
<div class="<?= $class_group; ?>">
    <label class="control-label" for="<?= $this->getFieldId() ?>"><?= $this->getLabelStyle($this->getElement(1)) ?></label>
    <div class="input-group">
        <span class="input-group-addon"><img src="<?= $link ?>" onclick="javascript:this.src=\'<?= $link ?>&\'+Math.random();" alt="CAPTCHA image" /></span>
        <input class="form-control" id="<?= $this->getFieldId() ?>" name="<?= $this->getFieldName() ?>" type="text" maxlength="5" />
    </div>
</div>
