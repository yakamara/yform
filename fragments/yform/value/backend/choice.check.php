<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$notices = [];
if ($yfield->getElement('notice')) {
    $notices[] = \Redaxo\Core\Translation\I18n::translate($yfield->getElement('notice'), false);
}
if (isset($yfield->params['warning_messages'][$yfield->getId()]) && !$yfield->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">' . \Redaxo\Core\Translation\I18n::translate($yfield->params['warning_messages'][$yfield->getId()], false) . '</span>';
}

if (!isset($groupAttributes)) {
    $groupAttributes = [];
}

$groupClass = 'form-check-group';
if (isset($groupAttributes['class']) && is_array($groupAttributes['class'])) {
    $groupAttributes['class'][] = $groupClass;
} elseif (isset($groupAttributes['class'])) {
    $groupAttributes['class'] .= ' ' . $groupClass;
} else {
    $groupAttributes['class'] = $groupClass;
}

if (!isset($elementAttributes)) {
    $elementAttributes = [];
}
$elementClass = trim(($choiceList->isMultiple() ? 'checkbox' : 'radio') . ' ' . $yfield->getWarningClass());
if (isset($elementAttributes['class']) && is_array($elementAttributes['class'])) {
    $elementAttributes['class'][] = $elementClass;
} elseif (isset($elementAttributes['class'])) {
    $elementAttributes['class'] .= ' ' . $elementClass;
} else {
    $elementAttributes['class'] = $elementClass;
}

?>

<?php $choiceOutput = function (\Yakamara\YForm\Choice\ChoiceView $view) use ($elementAttributes, $yfield) {
    ?>
    <div<?= \Redaxo\Core\Util\Str::buildAttributes($elementAttributes) ?>>
        <label>
            <input
                value="<?= \Redaxo\Core\View\escape($view->getValue()) ?>"
                <?= in_array($view->getValue(), $yfield->getValue(), true) ? ' checked="checked"' : '' ?>
                <?= $view->getAttributesAsString() ?>
            />
            <i class="form-helper"></i>
            <?= $view->getLabel() ?>
        </label>
    </div>
<?php
} ?>

<?php $choiceGroupOutput = static function (\Yakamara\YForm\Choice\ChoiceGroupView $view) use ($choiceOutput, $yfield) {
        ?>
    <div class="form-check-group">
        <label><?= \Redaxo\Core\View\escape($view->getLabel()) ?></label>
        <?php foreach ($view->getChoices() as $choiceView): ?>
            <?php $choiceOutput($choiceView) ?>
        <?php endforeach ?>
    </div>
<?php
    } ?>

<?php
    if (!isset($groupAttributes['id'])) {
        $groupAttributes['id'] = $yfield->getHTMLId();
    }
 ?>

<div<?= \Redaxo\Core\Util\Str::buildAttributes($groupAttributes) ?>>
    <?php if ($yfield->getLabel()): ?>
        <label class="control-label" for="<?= $yfield->getFieldId() ?>">
            <?= \Redaxo\Core\View\escape($yfield->getLabelStyle($yfield->getLabel())) ?>
        </label>
    <?php endif ?>

    <?php foreach ($choiceListView->getPreferredChoices() as $view): ?>
        <?php $view instanceof \Yakamara\YForm\Choice\ChoiceGroupView ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
    <?php endforeach ?>

    <?php foreach ($choiceListView->getChoices() as $view): ?>
        <?php $view instanceof \Yakamara\YForm\Choice\ChoiceGroupView ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
    <?php endforeach ?>

    <?php if ($notices): ?>
        <p class="help-block small"><?= implode('<br />', $notices) ?></p>
    <?php endif ?>
</div>
