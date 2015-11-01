<p class="formsubmit <?php echo $this->getHTMLClass() ?>">
    <input type="submit" class="submit <?php echo $this->getElement(4), ' ', $this->getWarningClass() ?>" name="<?php echo $this->getFieldName() ?>" id="<?php echo $this->getFieldId() ?>" value="<?php echo htmlspecialchars(stripslashes(rex_i18n::translate($this->getValue()))) ?>" />
</p>
