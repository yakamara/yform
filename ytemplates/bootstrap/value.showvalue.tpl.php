<?php
$class_group = trim('form-group yform-element ' . $this->getHTMLClass());
?>
<div class="<?= $class_group ?>"  id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label"><?php echo $this->getLabel() ?></label>
    <p class="form-control-static"><?php echo htmlspecialchars(stripslashes($this->getValue())) ?></p>
    <input type="hidden" name="<?php echo $this->getFieldName() ?>" value="<?php echo htmlspecialchars(stripslashes($this->getValue())) ?>" />
</div>
