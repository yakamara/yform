<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$fieldkey ??= 1;
$relationKey ??= 1;
$prototypeForm ??= '';
$forms ??= [];
$prioFieldName ??= '';

$class_group = trim('form-group ' . $yfield->getHTMLClass()); // . ' ' . $yfield->getWarningClass()

$notice = [];
if ('' != $yfield->getElement('notice')) {
    $notice[] = \Redaxo\Core\Translation\I18n::translate($yfield->getElement('notice'), false);
}
if (isset($yfield->params['warning_messages'][$yfield->getId()]) && !$yfield->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . \Redaxo\Core\Translation\I18n::translate($yfield->params['warning_messages'][$yfield->getId()], false) . '</span>';
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$prototypeForm = $yfield->parse('be_manager_inline_relation_form', ['counterfieldkey' => $fieldkey . '-' . \Redaxo\Core\View\escape($relationKey), 'form' => $prototypeForm, 'prioFieldName' => $prioFieldName]);

$sortable = 'data-yform-be-relation-sortable';
if ('' == $prioFieldName) {
    $sortable = '';
}

$fieldkey = 'y' . sha1($fieldkey . '-' . \Redaxo\Core\View\escape($relationKey)); // no number first

echo '

    <div class="' . $class_group . '" id="' . $fieldkey . '" data-yform-be-relation-form="' . \Redaxo\Core\View\escape($prototypeForm) . '" data-yform-be-relation-key="' . \Redaxo\Core\View\escape($relationKey) . '" data-yform-be-relation-index="' . count($forms) . '">
        <label class="control-label" for="' . $yfield->getFieldId() . '">' . $yfield->getLabel() . ' </label>
        <div data-yform-be-relation-item="' . $fieldkey . '" ' . $sortable . ' class="yform-be-relation-wrapper">';

$counter = 1;
foreach ($forms as $form) {
    echo $yfield->parse('be_manager_inline_relation_form', ['counterfieldkey' => $fieldkey . '-' . $counter, 'form' => $form, 'prioFieldName' => $prioFieldName]);
    ++$counter;
}

echo '
        </div>
        <div class="btn-group btn-group-xs">
            <button type="button" class="btn btn-default addme" title="add" data-yform-be-relation-add="' . $fieldkey . '-' . $counter . '"><i class="rex-icon rex-icon-add-module"></i><span class="rex-hidden">+</span></button>
        </div>
        ' . $notice . '
    </div>';
