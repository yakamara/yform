<p class="formcaptcha" id="<?php echo $this->getHTMLId() ?>">
    <label class="captcha <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>">
        <?php echo $this->getLabelStyle($this->getElement(1)) ?>
    </label>
    <span class="as-label<?php echo $this->getWarningClass() ?>"><img
        src="<?php echo $link ?>"
        onclick="javascript:this.src=\'<?php echo $link ?>&\'+Math.random();"
        alt="CAPTCHA image"
        /></span>
    <input class="captcha <?php echo $this->getWarningClass() ?>" maxlength="5" size="5" id="<?php echo $this->getFieldId() ?>" name="<?php echo $this->getFieldName() ?>" type="text" />
</p>
