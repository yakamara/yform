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

$groupClass = trim('form-group ' . $yfield->getWarningClass());
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
$elementClass = 'form-control';

if (isset($yfield->params['fixdata'][$yfield->getName()]) && !isset($elementAttributes['disabled'])) {
    $elementAttributes['disabled'] = 'disabled';
}

if (isset($elementAttributes['class']) && is_array($elementAttributes['class'])) {
    $elementAttributes['class'][] = $elementClass;
} elseif (isset($elementAttributes['class'])) {
    $elementAttributes['class'] .= ' ' . $elementClass;
} else {
    $elementAttributes['class'] = $elementClass;
}
?>

<?php $choiceOutput = function (\Yakamara\YForm\Choice\ChoiceView $view) use ($yfield) {
    ?>
    <option
        value="<?= \Redaxo\Core\View\escape($view->getValue()) ?>"
        <?= in_array($view->getValue(), $yfield->getValue(), true) ? ' selected="selected"' : '' ?>
        <?= $view->getAttributesAsString() ?>
    >
        <?= $view->getLabel() ?>
    </option>
<?php
} ?>

<?php $choiceGroupOutput = static function (\Yakamara\YForm\Choice\ChoiceGroupView $view) use ($choiceOutput, $yfield) {
        ?>
    <optgroup label="<?= \Redaxo\Core\View\escape($view->getLabel()) ?>">
        <?php foreach ($view->getChoices() as $choiceView): ?>
            <?php $choiceOutput($choiceView) ?>
        <?php endforeach ?>
    </optgroup>
<?php
    } ?>

<?php
    if (!isset($groupAttributes['id'])) {
        $groupAttributes['id'] = $yfield->getHTMLId();
    }

    // RexSelectStyle im Backend nutzen
    $useRexSelectStyle = \Redaxo\Core\Core::isBackend();

    // RexSelectStyle nicht nutzen, wenn die Klasse `.selectpicker` gesetzt ist
    if (isset($elementAttributes['class']) && str_contains($elementAttributes['class'], 'selectpicker')) {
        $useRexSelectStyle = false;
    }
    // RexSelectStyle nicht nutzen, wenn das Selectfeld mehrzeilig ist
    if (isset($elementAttributes['size']) && (int) $elementAttributes['size'] > 1) {
        $useRexSelectStyle = false;
    }
 ?>
<div<?= \Redaxo\Core\Util\Str::buildAttributes($groupAttributes) ?>>
    <?php if ($yfield->getLabel()): ?>
        <label class="control-label" for="<?= $yfield->getFieldId() ?>">
            <?= \Redaxo\Core\View\escape($yfield->getLabelStyle($yfield->getLabel())) ?>
        </label>
    <?php endif ?>


    <?php if ($useRexSelectStyle): ?>
    <div class="rex-select-style">
    <?php endif ?>
    <select<?= \Redaxo\Core\Util\Str::buildAttributes($elementAttributes) ?>>
        <?php if ($choiceList->getPlaceholder() && !$choiceList->isMultiple()): ?>
            <option value=""><?= \Redaxo\Core\View\escape($choiceList->getPlaceholder()) ?></option>
        <?php endif ?>

        <?php foreach ($choiceListView->getPreferredChoices() as $view): ?>
            <?php $view instanceof \Yakamara\YForm\Choice\ChoiceGroupView ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
        <?php endforeach ?>

        <?php if ($choiceListView->getPreferredChoices()): ?>
            <option disabled="disabled">-------------------</option>
        <?php endif ?>

        <?php foreach ($choiceListView->getChoices() as $view): ?>
            <?php $view instanceof \Yakamara\YForm\Choice\ChoiceGroupView ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
        <?php endforeach ?>
    </select>
    <?php if ($useRexSelectStyle): ?>
    </div>
    <?php endif ?>

    <?php if ($notices): ?>
        <p class="help-block small"><?= implode('<br />', $notices) ?></p>
    <?php endif ?>
</div>
