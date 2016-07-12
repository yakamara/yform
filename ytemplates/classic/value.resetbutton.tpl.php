<p class="formsubmit <?php echo $this->getHTMLClass() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="text <?php echo $this->getElement(4) ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>
    <input type="reset" class="submit <?php echo $this->getElement(4) ?>" id="<?php echo $this->getFieldId() ?>" value="<?php echo htmlspecialchars($this->getValue()) ?>" />
</p>
