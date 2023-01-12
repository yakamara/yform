<?php

/**
 * @var rex_yform_value_be_manager_relation $this
 * @psalm-scope-this rex_yform_value_be_manager_relation
 */

$fieldkey = $fieldkey ?? 1;
$relationKey = $relationKey ?? 1;
$prototypeForm = $prototypeForm ?? '';
$forms = $forms ?? [];
$prioFieldName = $prioFieldName ?? '';

$class_group = trim('form-group ' . $this->getHTMLClass()); // . ' ' . $this->getWarningClass()

$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>';
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$prototypeForm = $this->parse('value.be_manager_inline_relation_form.tpl.php', ['counterfieldkey' => $fieldkey.'-'.rex_escape($relationKey), 'form' => $prototypeForm, 'prioFieldName' => $prioFieldName]);

$sortable = 'data-yform-be-relation-sortable';
if ('' == $prioFieldName) {
    $sortable = '';
}

$fieldkey = 'y'.sha1($fieldkey.'-'.rex_escape($relationKey)); // no number first

echo '

    <div class="'.$class_group.'" id="'.$fieldkey.'" data-yform-be-relation-form="'.rex_escape($prototypeForm).'" data-yform-be-relation-key="'.rex_escape($relationKey).'" data-yform-be-relation-index="'.count($forms).'">
        <label class="control-label" for="'.$this->getFieldId().'">'.$this->getLabel().' </label>
        <div data-yform-be-relation-item="'.$fieldkey.'" '.$sortable.' class="yform-be-relation-wrapper">';

$counter = 1;
foreach ($forms as $form) {
    echo $this->parse('value.be_manager_inline_relation_form.tpl.php', ['counterfieldkey' => $fieldkey.'-'.$counter, 'form' => $form, 'prioFieldName' => $prioFieldName]);
    ++$counter;
}

echo '
        </div>
        <div class="btn-group btn-group-xs">
            <button type="button" class="btn btn-default addme" title="add" data-yform-be-relation-add="'.$fieldkey.'-'.$counter.'"><i class="rex-icon rex-icon-add-module"></i></button>
        </div>
        '.$notice.'
    </div>';
