<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$options ??= [];

$notices = [];
if ('' != $yfield->getElement('notice')) {
    $notices[] = \Redaxo\Core\Translation\I18n::translate($yfield->getElement('notice'), false);
}
if (isset($yfield->params['warning_messages'][$yfield->getId()]) && !$yfield->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">' . \Redaxo\Core\Translation\I18n::translate($yfield->params['warning_messages'][$yfield->getId()], false) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notices) . '</p>';
}

?>

<?php if ('' != trim($yfield->getLabel())): ?>
<div class="checkbox-group form-group">
    <label class="control-label"><?= $yfield->getLabel() ?></label>

<?php endif ?>

<?php foreach ($options as $k => $v): ?>
    <?php
    $class_group = trim('checkbox ' . $yfield->getHTMLClass() . ' ' . $yfield->getWarningClass());
    ?>
    <div class="<?= $class_group ?>" id="<?= $yfield->getHTMLId($k) ?>">
        <label>
            <input type="checkbox" name="<?= $yfield->getFieldName() ?>[]" value="<?= $k ?>"<?= in_array($k, $yfield->getValue()) ? ' checked="checked"' : '' ?> />
            <?= $yfield->getLabelStyle($v) ?>
        </label>
    </div>
<?php endforeach ?>
<?= $notice ?>

<?php if ('' != trim($yfield->getLabel())): ?>
</div>
<?php endif ?>
