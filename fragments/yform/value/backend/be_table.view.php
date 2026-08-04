<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$columns ??= [];
$data ??= [];

$class_group = trim('form-group ' . $yfield->getHTMLClass() . ' ' . $yfield->getWarningClass());

$data_index = 0;
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

$main_id = $yfield->params['this']->getObjectparams('main_id');

?>
<div class="<?= $class_group ?>" id="<?= $yfield->getHTMLId() ?>">
    <label class="control-label" for="<?= $yfield->getFieldId() ?>"><?= $yfield->getLabel() ?></label>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th class="type-<?= $column['field']->getElement(0) ?>"><?= \Redaxo\Core\View\escape($column['label']) ?></th>
            <?php endforeach ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $data_index => $row): ?>
            <tr>
                <?php foreach ($columns as $i => $column): ?>
                    <?php
                    $rowData = array_values($row);

                    /** @var \Yakamara\YForm\Value\AbstractValue $field */
                    $field = $column['field'];
                    $field->params['form_output'] = [];
                    $field->params['this']->setObjectparams('form_name', $yfield->getParam('form_name') . '][' . $yfield->getId() . '][' . $i);
                    $field->params['this']->setObjectparams('main_id', $main_id);
                    $field->params['this']->canEdit(false);
                    $field->params['form_name'] = $field->getName();
                    $field->params['form_label_type'] = 'html';
                    $field->params['send'] = false;

                    if ('be_manager_relation' == $field->getElement(0)) {
                        $field->params['main_table'] = $field->getElement('table');
                        $field->setName($field->getElement('field'));
                    }
                    $field->setValue($rowData[$i] ?? '');
                    $field->setId($data_index);
                    $field->enterObject();
                    $field_output = trim($field->params['form_output'][$field->getId()]);

                    ?>
                    <td class="be-value-input type-<?= $column['field']->getElement(0) ?>" data-title="<?= \Redaxo\Core\View\escape($column['label'], 'html_attr') ?>"><?= $field_output ?></td>
                <?php endforeach ?>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <?= $notice ?>
</div>
