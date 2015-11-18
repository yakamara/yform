<?php foreach ($options as $k => $v): ?>
    <?php
    $class_group = trim('checkbox yform-element ' . $this->getHTMLClass($k) . ' ' . $this->getWarningClass());
    ?>
    <div class="<?= $class_group ?>" id="<?= $this->getHTMLId($k) ?>">
        <label>
            <input type="checkbox" name="<?= $this->getFieldName() ?>[]" value="<?= $k ?>"<?= in_array($k, $this->getValue()) ? ' checked="checked"' : '' ?> />
            <?= $this->getLabelStyle($v) ?>
        </label>
    </div>
<?php endforeach ?>
