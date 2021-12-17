<?php

/**
 * @var rex_yform_value_checkbox $this
 * @psalm-scope-this rex_yform_value_checkbox
 */

$options = $options ?? [];

$notices = [];
if ('' != $this->getElement('notice')) {
    $notices[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notices) . '</p>';
}

?>

<?php if ('' != trim($this->getLabel())): ?>
<div class="checkbox-group form-group">
    <label class="control-label"><?php echo $this->getLabel() ?></label>

<?php endif; ?>

<?php foreach ($options as $k => $v): ?>
    <?php
    $class_group = trim('checkbox ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
    ?>
    <div class="<?= $class_group ?>" id="<?= $this->getHTMLId($k) ?>">
        <label>
            <input type="checkbox" name="<?= $this->getFieldName() ?>[]" value="<?= $k ?>"<?= in_array($k, $this->getValue()) ? ' checked="checked"' : '' ?> />
            <?= $this->getLabelStyle($v) ?>
        </label>
    </div>
<?php endforeach ?>
<?php echo $notice; ?>

<?php if ('' != trim($this->getLabel())): ?>
</div>
<?php endif; ?>
