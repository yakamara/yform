<?php

/** @var rex_yform_value_checkbox $this */

$notices = array();
if ($this->getElement('notice') != "") {
    $notices[] = $this->getElement('notice');
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] =  '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block">' . implode("<br />", $notices) . '</p>';
}

$class_group = trim('checkbox yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$value = isset($value) ? $value : 1;

$attributes = [
    'type' => 'checkbox',
    'id' => $this->getFieldId(),
    'name' => $this->getFieldName(),
    'value' => $value,
];
if ($this->getValue() == $value) {
    $attributes['checked'] = 'checked';
}

$attributes = $this->getAttributeElements($attributes);

?>
<div class="<?= $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label>
        <input <?= implode(' ', $attributes) ?> />
        <?php echo $this->getLabel() ?>
    </label>
    <?php echo $notice; ?>
</div>


