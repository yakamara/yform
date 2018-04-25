<?php
$notices = [];
if ($this->getElement('notice') != '') {
    $notices[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">'.rex_i18n::translate($this->params['warning_messages'][$this->getId()], false).'</span>';
}

$groupAttributes['class'] = trim('form-group '.$this->getWarningClass());
$elementAttributes['class'] = 'form-control';
if ($options['expanded']) {
    $groupAttributes['class'] = 'form-check-group';
    $elementAttributes['class'] = trim(($options['multiple'] ? 'checkbox' : 'radio').' '.$this->getWarningClass());
}
?>

<div<?= rex_string::buildAttributes($groupAttributes) ?>>
<?php if ($this->getLabel() != ''): ?>
    <label class="form-control-label" for="<?=  $this->getFieldId() ?>"><?= $this->getLabelStyle($this->getLabel()) ?></label>
<?php endif; ?>
<?php if ($options['expanded']): ?>
    <?php if (count($choiceListView->getPreferredChoices())): ?>
        <?php foreach ($choiceListView->getPreferredChoices() as $view): ?>
            <?php if ($view instanceof ChoiceGroupView): ?>
            <div class="form-check-group">
                <label><?= $view->getLabel() ?></label>
                <?php foreach ($view->getChoices() as $choiceView): ?>
                    <div<?= rex_string::buildAttributes($elementAttributes) ?>>
                        <label>
                            <input value="<?= $choiceView->getValue() ?>"
                                <?= (in_array($choiceView->getValue(), $this->getValue(), true) ? ' checked="checked"' : '') ?>
                                <?= $choiceView->getAttributesAsString() ?> /><i class="form-helper"></i><?= $choiceView->getLabel() ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php elseif ($view instanceof ChoiceView): ?>
            <div<?= rex_string::buildAttributes($elementAttributes) ?>>
                <label>
                    <input value="<?= $view->getValue() ?>"
                        <?= (in_array($view->getValue(), $this->getValue(), true) ? ' checked="checked"' : '') ?>
                        <?= $view->getAttributesAsString() ?> /><i class="form-helper"></i><?= $view->getLabel() ?>
                </label>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (count($choiceListView->getChoices())): ?>
        <?php foreach ($choiceListView->getChoices() as $view): ?>
            <?php if ($view instanceof ChoiceGroupView): ?>
            <div class="form-check-group">
                <label><?= $view->getLabel() ?></label>
                <?php foreach ($view->getChoices() as $choiceView): ?>
                    <div<?= rex_string::buildAttributes($elementAttributes) ?>>
                        <label>
                            <input value="<?= $choiceView->getValue() ?>"
                                <?= (in_array($choiceView->getValue(), $this->getValue(), true) ? ' checked="checked"' : '') ?>
                                <?= $choiceView->getAttributesAsString() ?> /><i class="form-helper"></i><?= $choiceView->getLabel() ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php elseif ($view instanceof ChoiceView): ?>
            <div<?= rex_string::buildAttributes($elementAttributes) ?>>
                <label>
                    <input value="<?= $view->getValue() ?>"
                        <?= (in_array($view->getValue(), $this->getValue(), true) ? ' checked="checked"' : '') ?>
                        <?= $view->getAttributesAsString() ?> /><i class="form-helper"></i><?= $view->getLabel() ?>
                </label>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
<?php else: ?>
    <select<?= rex_string::buildAttributes($elementAttributes) ?>>
    <?php if (null !== $options['placeholder'] && !$options['multiple']): ?>
        <option value=""><?= $options['placeholder'] ?></option>
    <?php endif; ?>
    <?php if (count($choiceListView->getPreferredChoices())): ?>
        <?php foreach ($choiceListView->getPreferredChoices() as $view): ?>
            <?php if ($view instanceof ChoiceGroupView): ?>
            <optgroup label="<?= $view->getLabel() ?>">
            <?php foreach ($view->getChoices() as $choiceView): ?>
                <option
                    value="<?= $choiceView->getValue() ?>"
                    <?= (in_array($choiceView->getValue(), $this->getValue(), true) ? ' selected="selected"' : '') ?>
                    <?= $choiceView->getAttributesAsString() ?>>
                    <?= $choiceView->getLabel() ?>
                </option>
            <?php endforeach; ?>
            </optgroup>
            <?php elseif ($view instanceof ChoiceView): ?>
            <option
                value="<?= $view->getValue() ?>"
                <?= (in_array($view->getValue(), $this->getValue(), true) ? ' selected="selected"' : '') ?>
                <?= $view->getAttributesAsString() ?>>
                <?= $view->getLabel() ?>
            </option>
            <?php endif; ?>
        <?php endforeach; ?>
        <option disabled="disabled">-------------------</option>
    <?php endif; ?>

    <?php foreach ($choiceListView->getChoices() as $view): ?>
        <?php if ($view instanceof ChoiceGroupView): ?>
        <optgroup label="<?= $view->getLabel() ?>">
        <?php foreach ($view->getChoices() as $choiceView): ?>
            <option
                value="<?= $choiceView->getValue() ?>"
                <?= (in_array($choiceView->getValue(), $this->getValue(), true) ? ' selected="selected"' : '') ?>
                <?= $choiceView->getAttributesAsString() ?>>
                <?= $choiceView->getLabel() ?>
            </option>
        <?php endforeach; ?>
        </optgroup>
        <?php elseif ($view instanceof ChoiceView): ?>
        <option
            value="<?= $view->getValue() ?>"
            <?= (in_array($view->getValue(), $this->getValue(), true) ? ' selected="selected"' : '') ?>
            <?= $view->getAttributesAsString() ?>>
            <?= $view->getLabel() ?>
        </option>
        <?php endif; ?>
    <?php endforeach; ?>
    </select>
<?php endif; ?>

<?php if (count($notices)): ?>
    <p class="help-block"><?= implode('<br />', $notices) ?></p>
<?php endif; ?>
</div>
