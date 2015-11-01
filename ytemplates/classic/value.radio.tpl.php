<p class="formradio formlabel-<?php echo $this->getName() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="radio <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <div class="radios <?php echo $this->getWarningClass() ?>" id="<?php echo $this->getFieldId() ?>">
        <?php $counter = 0 ?>
        <?php foreach ($options as $key => $value): ?>
            <?php $id = $this->getFieldId() . '-' . $counter++ ?>
            <p class="radio">
                <input type="radio" class="radio" id="<?php echo $id ?>" name="<?php echo $this->getFieldName() ?>" value="<?php echo htmlspecialchars($key) ?>"<?php echo $key == $this->getValue() ? ' checked="checked"' : '' ?> />
                <label for="<?php echo $id ?>"><?php echo $this->getLabelStyle($value) ?></label>
            </p>
        <?php endforeach ?>
    </div>
</p>
