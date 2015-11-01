<p class="formselect formlabel-<?php echo $this->getName() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="select <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <select class="select <?php echo $this->getWarningClass() ?>" id="<?php echo $this->getFieldId() ?>" <?php echo $multiple ? 'name="' . $this->getFieldName() . '[]" multiple="multiple"' : 'name="' . $this->getFieldName() . '"', $this->getElement("disabled") ? ' disabled="disabled"' : '' ?>  size="<?php echo $size ?>">
        <?php foreach ($options as $key => $value): ?>
            <option value="<?php echo htmlspecialchars($key) ?>"<?php echo in_array((string) $key, $this->getValue()) ? ' selected="selected"' : '' ?>><?php echo $this->getLabelStyle($value) ?></option>
        <?php endforeach ?>
    </select>
</p>
