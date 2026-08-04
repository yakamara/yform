<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$options ??= [];
$link ??= '';
$valueName ??= '';

$class_group = trim('form-group ' . $yfield->getHTMLClass() . ' ' . $yfield->getWarningClass());

$id = sprintf('%u', crc32($yfield->params['form_name'] . random_int(0, 100) . $yfield->getId()));

$notice = [];
if ('' != $yfield->getElement('notice')) {
    $notice[] = \Redaxo\Core\Translation\I18n::translate($yfield->getElement('notice'), false);
}
if (isset($yfield->params['warning_messages'][$yfield->getId()]) && !$yfield->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . \Redaxo\Core\Translation\I18n::translate($yfield->params['warning_messages'][$yfield->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

?>
<?php if ($yfield->getRelationType() < 2): ?>
    <div data-be-relation-wrapper="<?= $yfield->getFieldName() ?>" class="<?= $class_group ?>" id="<?= $yfield->getHTMLId() ?>">
        <label class="control-label" for="<?= $yfield->getFieldId() ?>"><?= $yfield->getLabel() ?></label>
        <?php

        $attributes = [];
    $attributes['class'] = 'form-control';
    $attributes['id'] = $yfield->getFieldId();

    $select = new \Redaxo\Core\Form\Select\Select();

    if (1 == $yfield->getRelationType()) {
        $select->setName($yfield->getFieldName() . '[]');
        $select->setMultiple();
        $select->setSize($yfield->getRelationSize());
    } else {
        $select->setName($yfield->getFieldName());
    }

$attributes = $yfield->getAttributeArray($attributes, ['required', 'readonly', 'disabled']);

$select->setAttributes($attributes);
foreach ($options as $option) {
    $select->addOption($option['name'], $option['id']);
}

$select->setSelected($yfield->getValue());
echo $select->get();
?>
        <?= $notice ?>
    </div>
<?php else: ?>
    <div data-be-relation-wrapper="<?= $yfield->getFieldName() ?>" class="<?= $class_group ?>" id="<?= $yfield->getHTMLId() ?>">
        <label class="control-label" for="<?= $yfield->getFieldId() ?>"><?= $yfield->getLabel() ?></label>
        <?php
$e = [];
    if (4 == $yfield->getRelationType()) {
        echo \Yakamara\YForm\Manager\RelationWidget::getRelationWidget($id, $yfield->getFieldName(), $yfield->getValue(), $link, $yfield->params['main_id']);
    } elseif (2 == $yfield->getRelationType()) {
        $name = $yfield->getFieldName();
        $args = [];
        $args['link'] = $link;
        $args['fieldName'] = $yfield->getRelationSourceTableName() . '.' . $yfield->getName();
        $args['valueName'] = $valueName;
        $_csrf_key = \Yakamara\YForm\Manager\Table\Table::get($yfield->relation['target_table'])->getCSRFKey();
        $args += \Redaxo\Core\Security\CsrfToken::factory($_csrf_key)->getUrlParams();
        $value = implode(',', $yfield->getValue());
        echo \Yakamara\YForm\Manager\RelationWidget::getSingleWidget($id, $name, $value, $args);
    } else {
        $name = $yfield->getFieldName();
        $args = [];
        $args['link'] = $link;
        $args['options'] = $options;
        $args['fieldName'] = $yfield->getRelationSourceTableName() . '.' . $yfield->getName();
        $args['size'] = $yfield->getRelationSize();
        $args['attributes'] = $yfield->getAttributeArray([], ['required', 'readonly']);
        $_csrf_key = \Yakamara\YForm\Manager\Table\Table::get($yfield->relation['target_table'])->getCSRFKey();
        $args += \Redaxo\Core\Security\CsrfToken::factory($_csrf_key)->getUrlParams();
        $value = implode(',', $yfield->getValue());
        echo \Yakamara\YForm\Manager\RelationWidget::getMultipleWidget($id, $name, $value, $args);
    }
    ?>
        <?= $notice ?>
    </div>
<?php endif;
