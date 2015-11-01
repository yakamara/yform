<p class="formtext <?php echo $this->getHTMLClass() ?>"  id="<?php echo $this->getHTMLId() ?>">
    <label class="text" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <input type="hidden" name="<?php echo $this->getFieldName() ?>" value="<?php echo htmlspecialchars(stripslashes($this->getValue())) ?>" />
    <input type="text" class="inp_disabled" disabled="disabled" id="<?php echo $this->getFieldId() ?>" value="<?php echo htmlspecialchars(stripslashes($this->getValue())) ?>" />
</p>
