<?php

$class = 'form-captcha';
$class .= $this->getElement('required') ? 'form-is-required ' : '';
$notice = '' != $this->getElement('notice') ? '<p class="help-block">' . $this->getElement('notice') . '</p>' : '';

$class_group = trim('form-group ' . $class . $this->getWarningClass());
$class_control = trim('form-control');

?>
<div class="<?php echo $class_group ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabelStyle($this->getElement(1)) ?></label>
    <div class="input-group">
        <span class="input-group-addon"><img id="<?php echo $this->getFieldId() ?>-captcha" src="<?php echo $link ?>" onclick="javascript:this.src='<?php echo $link ?>&'+Math.random();" alt="CAPTCHA" /></span>
        <input class="form-control" type="text" name="<?php echo $this->getFieldName() ?>" id="<?php echo $this->getFieldId() ?>" value="" maxlength="5" size="5" />
        <span class="input-group-btn"><a class="btn btn-default" href="javascript:void();" onclick="document.getElementById('<?php echo $this->getFieldId() ?>-captcha').src='<?php echo $link ?>&'+Math.random(); return false;">Reload</a></span>
    </div>
    <?php echo $notice ?>
</div>
