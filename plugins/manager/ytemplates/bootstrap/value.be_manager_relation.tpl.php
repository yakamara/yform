<?php

/**
 * @var rex_yform_value_be_manager_relation $this
 * @psalm-scope-this rex_yform_value_be_manager_relation
 */

$options = $options ?? [];
$link = $link ?? '';
$valueName = $valueName ?? '';

$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$id = sprintf('%u', crc32($this->params['form_name']. random_int(0, 100). $this->getId()));

$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

?>
<?php if ($this->getRelationType() < 2): ?>
    <div data-be-relation-wrapper="<?php echo $this->getFieldName(); ?>" class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
        <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
        <?php

        $attributes = [];
    $attributes['class'] = 'form-control';
    $attributes['id'] = $this->getFieldId();

    $select = new rex_select();

    if (1 == $this->getRelationType()) {
        $select->setName($this->getFieldName() . '[]');
        $select->setMultiple();
        $select->setSize($this->getRelationSize());
    } else {
        $select->setName($this->getFieldName());
    }

$attributes = $this->getAttributeArray($attributes, ['required', 'readonly', 'disabled']);

$select->setAttributes($attributes);
foreach ($options as $option) {
    $select->addOption($option['name'], $option['id']);
}

$select->setSelected($this->getValue());
echo $select->get();
?>
        <?php echo $notice ?>
    </div>
<?php else: ?>
    <div data-be-relation-wrapper="<?php echo $this->getFieldName(); ?>" class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
        <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
        <?php
$e = [];
    if (4 == $this->getRelationType()) {
        echo \rex_var_yform_table_data::getRelationWidget($id, $this->getFieldName(), $this->getValue(), $link, $this->params['main_id']);
    } elseif (2 == $this->getRelationType()) {
        $name = $this->getFieldName();
        $args = [];
        $args['link'] = $link;
        $args['fieldName'] = $this->getRelationSourceTableName() . '.' . $this->getName();
        $args['valueName'] = $valueName;
        $value = implode(',', $this->getValue());
        echo \rex_var_yform_table_data::getSingleWidget($id, $name, $value, $args);
    } else {
        $name = $this->getFieldName();
        $args = [];
        $args['link'] = $link;
        $args['options'] = $options;
        $args['fieldName'] = $this->getRelationSourceTableName() . '.' . $this->getName();
        $args['size'] = $this->getRelationSize();
        $args['attributes'] = $this->getAttributeArray([], ['required', 'readonly']);
        $value = implode(',', $this->getValue());
        echo \rex_var_yform_table_data::getMultipleWidget($id, $name, $value, $args);
    }
    ?>
        <?php echo $notice ?>
    </div>
<?php endif;
