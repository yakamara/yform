<?php

$rows = $this->getElement('rows');
if (!$rows) {
    $rows = 10;
}

$attributes = [
    "class" => trim('textarea ' . $this->getWarningClass()),
    "name" => $this->getFieldName(),
    "id" => $this->getFieldId(),
    "rows" => $rows
];

$attributes = $this->getAttributeElements($attributes, ['placeholder', 'pattern', 'required', 'disabled', 'readonly']);
?>
<p class="formtextarea" id="<?php echo $this->getHTMLId() ?>">
    <label class="<?php echo trim('textarea ' . $this->getWarningClass()) ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>
    <textarea <?= implode(' ', $attributes) ?>><?php echo htmlspecialchars($this->getValue()) ?></textarea>
</p>
