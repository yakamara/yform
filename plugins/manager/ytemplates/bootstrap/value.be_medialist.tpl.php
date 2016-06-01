<?php

$buttonId = $counter;
$name = $this->getFieldName();
$value = htmlspecialchars($this->getValue());

$widget_params = [];
if ($this->getElement(4) != "") {
    $widget_params['category'] = intval($this->getElement(4));
}
$widget_params['preview'] = $this->getElement(3);
if ($this->getElement(5) != "") {
    $widget_params['types'] = trim($this->getElement(5));
}

$widget = rex_var_medialist::getWidget($buttonId, $name, $value, $widget_params);

$class_group = trim('form-group yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$notice = array();
if ($this->getElement('notice') != "") {
    $notice[] = $this->getElement('notice');
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] =  '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode("<br />", $notice) . '</p>';

} else {
    $notice = '';
}

?>
<div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <?php echo $widget; ?>
    <?php echo $notice ?>
</div>
