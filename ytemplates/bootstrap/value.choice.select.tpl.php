<?php

/** @var rex_yform_choice_list $choiceList */
/** @var rex_yform_choice_list_view $choiceListView */

$notices = [];
if ($this->getElement('notice')) {
    $notices[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">'.rex_i18n::translate($this->params['warning_messages'][$this->getId()], false).'</span>';
}

if (!isset($groupAttributes)) {
    $groupAttributes = [];
}

$groupClass = trim('form-group '.$this->getWarningClass());
if (isset($groupAttributes['class']) && is_array($groupAttributes['class'])) {
    $groupAttributes['class'][] = $groupClass;
} elseif (isset($groupAttributes['class'])) {
    $groupAttributes['class'] .= ' '.$groupClass;
} else {
    $groupAttributes['class'] = $groupClass;
}

if (!isset($elementAttributes)) {
    $elementAttributes = [];
}
$elementClass = 'form-control';
if (isset($elementAttributes['class']) && is_array($elementAttributes['class'])) {
    $elementAttributes['class'][] = $elementClass;
} elseif (isset($elementAttributes['class'])) {
    $elementAttributes['class'] .= ' '.$elementClass;
} else {
    $elementAttributes['class'] = $elementClass;
}
?>

<?php $choiceOutput = function (rex_yform_choice_view $view) {
    ?>
    <option
        value="<?= rex_escape($view->getValue()) ?>"
        <?= in_array($view->getValue(), $this->getValue(), true) ? ' selected="selected"' : '' ?>
        <?= $view->getAttributesAsString() ?>
    >
        <?= rex_escape($view->getLabel()) ?>
    </option>
<?php
} ?>

<?php $choiceGroupOutput = function (rex_yform_choice_group_view $view) use ($choiceOutput) {
        ?>
    <optgroup label="<?= rex_escape($view->getLabel()) ?>">
        <?php foreach ($view->getChoices() as $choiceView): ?>
            <?php $choiceOutput($choiceView) ?>
        <?php endforeach ?>
    </optgroup>
<?php
    } ?>

<div<?= rex_string::buildAttributes($groupAttributes) ?>>
    <?php if ($this->getLabel()): ?>
        <label class="control-label" for="<?= $this->getFieldId() ?>">
            <?= rex_escape($this->getLabelStyle($this->getLabel())) ?>
        </label>
    <?php endif ?>

    <select<?= rex_string::buildAttributes($elementAttributes) ?>>
        <?php if ($choiceList->getPlaceholder() && !$choiceList->isMultiple()): ?>
            <option value=""><?= rex_escape($choiceList->getPlaceholder()) ?></option>
        <?php endif ?>

        <?php foreach ($choiceListView->getPreferredChoices() as $view): ?>
            <?php $view instanceof rex_yform_choice_group_view ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
        <?php endforeach ?>

        <?php if ($choiceListView->getPreferredChoices()): ?>
            <option disabled="disabled">-------------------</option>
        <?php endif ?>

        <?php foreach ($choiceListView->getChoices() as $view): ?>
            <?php $view instanceof rex_yform_choice_group_view ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
        <?php endforeach ?>
    </select>

    <?php if ($notices): ?>
        <p class="help-block"><?= implode('<br />', $notices) ?></p>
    <?php endif ?>
</div>
