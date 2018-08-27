<?php

$class_group = trim('form-group yform-element ' . $this->getHTMLClass()); // . ' ' . $this->getWarningClass()

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

$prototypeForm = $this->parse('value.be_manager_inline_relation_form.tpl.php', ['counterfieldkey' => $fieldkey.'-__name__', 'form' => $prototypeForm]);

?>

    <div class="<?php echo $class_group ?>" id="<?php echo $fieldkey; ?>" data-yform-be-relation-form="<?php echo rex_escape($prototypeForm) ?>" data-yform-be-relation-index="<?php echo count($forms); ?>">
        <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabelStyle($this->relation['label']) ?> </label>

        <div data-yform-be-relation-item="<?php echo $fieldkey; ?>" data-yform-be-relation-sortable class="yform-be-relation-wrapper">
        <?php

        $counter = 1;
        foreach ($forms as $form) {
            echo $this->parse('value.be_manager_inline_relation_form.tpl.php', ['counterfieldkey' => $fieldkey.'-'.$counter, 'form' => $form]);
            ++$counter;
        }

        ?>
        </div>

        <?php
        echo '<div class="btn-group btn-group-xs">
            <button type="button" class="btn btn-default addme" title="add" data-yform-be-relation-add="'.$fieldkey.'-'.$counter.'"><i class="rex-icon rex-icon-add-module"></i></button>
        </div>';
        ?>

        <?php echo $notice ?>
    </div>
