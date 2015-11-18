<?php
$buttonId = $counter;
$name = $this->getFieldName();
$value = htmlspecialchars($this->getValue());
$categoryId = intval($this->getElement(4));
$preview = $this->getElement(3);
$types = trim($this->getElement(5));
$widget = rex_var_medialist::getWidget($buttonId, $name, $value, ['category' => $categoryId, 'preview' => $preview, 'types' => $types]);

$class_group = trim('form-group yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
?>
<div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <?php echo $widget; ?>
</div>
