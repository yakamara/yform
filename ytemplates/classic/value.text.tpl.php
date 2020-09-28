<?php
$type = $type ?? 'text';
$class = 'text' == $type ? '' : $type . ' ';
$value = $this->getValue();

$attributes = [
    'class' => trim('text ' . $class . ' ' . $this->getWarningClass()),
    'name' => $this->getFieldName(),
    'type' => $type,
    'id' => $this->getFieldId(),
    'value' => $value,
];
$attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);
?>
<p class="formtext <?php echo $class ? 'form' . $class : '' ?>formlabel-<?php echo $this->getName() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="<?php echo trim('text ' . $class . ' ' . $this->getWarningClass()) ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>
    <input <?= implode(' ', $attributes) ?> />
</p>
