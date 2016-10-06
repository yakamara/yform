<?php foreach ($options as $k => $v): ?>
    <p class="formcheckbox formlabel-<?php echo $this->getName($k) ?>" id="<?php echo $this->getHTMLId($k) ?>">
        <input type="checkbox" class="checkbox" name="<?php echo $this->getFieldName() ?>[]" id="<?php echo $this->getFieldId($k) ?>" value="<?php echo $k ?>" <?php echo in_array($k, $this->getValue()) ? ' checked="checked"' : '' ?> />
        <label class="checkbox" for="<?php echo $this->getFieldId($k) ?>"><?php echo $this->getLabelStyle($v) ?></label>
    </p>
<?php endforeach ?>
