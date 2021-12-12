<?php

/**
 * @var rex_yform_value_be_table $this
 * @psalm-scope-this rex_yform_value_be_table
 */

$columns = $columns ?? [];
$data = $data ?? [];

$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$data_index = 0;
$notice = [];
if ('' != $this->getElement('notice')) {
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

$ytemplates = $this->params['this']->getObjectparams('form_ytemplate');
$main_id = $this->params['this']->getObjectparams('main_id');

?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th class="type-<?= $column['field']->getElement(0) ?>"><?php echo htmlspecialchars($column['label']) ?></th>
            <?php endforeach ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $data_index => $row): ?>
            <tr>
                <?php foreach ($columns as $i => $column): ?>
                    <?php
                    $rowData = array_values($row);
                    /** @var $field rex_yform_value_abstract $field */
                    $field = $column['field'];
                    $field->params['form_output'] = [];
                    $field->params['this']->setObjectparams('form_name', $this->getName() . '.' . $i);
                    $field->params['this']->setObjectparams('form_ytemplate', $ytemplates);
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

                    /** @var array $field->params['form_output'] */
                    $field_output = trim($field->params['form_output'][$field->getId()]);
                    ?>
                    <td class="be-value-input type-<?= $column['field']->getElement(0) ?>" data-title="<?= rex_escape($column['label'], 'html_attr') ?>"><?= $field_output ?></td>
                <?php endforeach ?>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <?php echo $notice ?>
</div>
