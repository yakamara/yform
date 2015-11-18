<?php
$buttonId = $counter;
$categoryId = 0;
$name = $this->getFieldName();
$value = htmlspecialchars($this->getValue());
$widget = rex_var_media::getWidget($buttonId, $name, $value, ['category' => $categoryId]);

$class_group = trim('form-group yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
?>
<div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <?php echo $widget; ?>
</div>
