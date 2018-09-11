<?php

$class_group = trim('form-group ' . $this->getHTMLClass()); // . ' ' . $this->getWarningClass()

$id = crc32($this->params['form_name']) . rand(0, 10000) . $this->getId();

$notice = [];
if ($this->getElement('notice') != '') {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$prototypeForm = $this->parse('value.be_manager_inline_relation_form.tpl.php', ['counterfieldkey' => $fieldkey.'-__name__', 'form' => $prototypeForm, 'prioFieldName' => $prioFieldName]);

$sortable = 'data-yform-be-relation-sortable';
if ($prioFieldName == ''){
    $sortable = '';
}

echo '

    <div class="'.$class_group.'" id="'.$fieldkey.'" data-yform-be-relation-form="'.rex_escape($prototypeForm).'" data-yform-be-relation-index="'.count($forms).'">
        <label class="control-label" for="'.$this->getFieldId().'">'.$this->getLabelStyle($this->relation['label']).' </label>

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
