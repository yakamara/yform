<p class="formtextarea" id="<?php echo $this->getHTMLId() ?>">
    <label class="<?php echo trim('textarea ' . $this->getWarningClass()) ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>
    <textarea class="<?php echo trim('textarea ' . $this->getElement(5) . ' ' . $this->getWarningClass()) ?>" name="<?php echo $this->getFieldName() ?>" id="<?php echo $this->getFieldId() ?>" cols="80" rows="10" <?php echo $this->getAttributeElements('placeholder'), $this->getAttributeElements('pattern'), $this->getAttributeElements('required', true), $this->getAttributeElements('disabled', true), $this->getAttributeElements('readonly', true) ?>><?php echo htmlspecialchars($this->getValue()) ?></textarea>
</p>
