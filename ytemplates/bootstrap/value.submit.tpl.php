<?php

    $class_group   = trim('form-group form-submit ' . $this->getElement(4) . ' ' . $this->getWarningClass());

?>
<button class="btn btn-primary" type="submit" name="<?php echo $this->getFieldName() ?>" id="<?php echo $this->getFieldId() ?>"><?php echo htmlspecialchars(stripslashes(rex_i18n::translate($this->getValue()))) ?></button>
