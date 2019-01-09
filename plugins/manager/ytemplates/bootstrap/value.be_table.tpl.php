<?php
$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$data_index = 0;
$notice     = [];
if ($this->getElement('notice') != '') {
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
$main_id    = $this->params['this']->getObjectparams('main_id');

?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th><?php echo htmlspecialchars($column['label']) ?></th>
            <?php endforeach ?>
            <th class="rex-table-action"><a class="btn btn-xs btn-primary" id="<?= $this->getHTMLId() ?>-add-row" href="javascript:void(0);"><i class="rex-icon rex-icon-add"></i> <?php echo rex_i18n::msg('yform_add_row') ?></a></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $data_index => $row): ?>
            <tr>
                <?php foreach ($columns as $i => $column): ?>
                    <td class="be-value-input" data-title="<?= rex_escape($column['label'], 'html_attr') ?>">
                        <?php
                        $field = $column['field'];
                        $field->params['this']->setObjectparams('form_name', $this->getId() . '.' . $i);
                        $field->params['this']->setObjectparams('form_ytemplate', $ytemplates);
                        $field->params['this']->setObjectparams('main_id', $main_id);
                        $field->params['form_name']       = $field->getName();
                        $field->params['form_label_type'] = 'html';
                        $field->params['send']            = false;

                        if ($field->getElement(0) == 'be_manager_relation') {
                            $field->params['main_table'] = $field->getElement('table');
                            $field->setName($field->getElement('field'));
                        }
                        $field->setValue($row[$i] ?: '');
                        $field->setId($data_index);
                        $field->enterObject();
                        echo $field->params['form_output'][$field->getId()]
                        ?>
                    </td>
                <?php endforeach ?>
                <td class="delete-row"><a class="btn btn-xs btn-delete" href="javascript:void(0)"><i class="rex-icon rex-icon-delete"></i> <?php echo rex_i18n::msg('yform_delete') ?></a></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <a class="btn btn-primary btn-xs add-mobile-btn" id="<?= $this->getHTMLId() ?>-add-mobile-row" href="javascript:void(0);"><i class="rex-icon rex-icon-add"></i> <?php echo rex_i18n::msg('yform_add_row') ?></a>

    <script type="text/javascript">
        (function () {
            var wrapper = jQuery('#<?php echo $this->getHTMLId() ?>'),
                be_table_cnt = <?= (int)$data_index ?>;

            wrapper.find('#<?= $this->getHTMLId() ?>-add-row, #<?= $this->getHTMLId() ?>-add-mobile-row').click(function () {
                var $this = $(this),
                    $table = $this.parents('.formbe_table').children('table'),
                    tr = $('<tr/>'),
                    regexp = [
                        // REX_MEDIA
                        new RegExp("(REX_MEDIA_)", 'g'),
                        new RegExp("(openREXMedia\\()", 'g'),
                        new RegExp("(addREXMedia\\()", 'g'),
                        new RegExp("(deleteREXMedia\\()", 'g'),
                        new RegExp("(viewREXMedia\\()", 'g'),
                        // REX_MEDIALIST
                        new RegExp("(REX_MEDIALIST_SELECT_)", 'g'),
                        new RegExp("(moveREXMedialist\\()", 'g'),
                        new RegExp("(openREXMedialist\\()", 'g'),
                        new RegExp("(addREXMedialist\\()", 'g'),
                        new RegExp("(deleteREXMedialist\\()", 'g'),
                        new RegExp("(viewREXMedialist\\()", 'g'),
                    ],
                    row_html = '\
                    <?php foreach ($columns as $i => $column): ?>\
                            <td class="be-value-input" data-title="<?= $column['label'] ?>"><?php
                        $field = $columns[$i]['field'];
                        $field->params['this']->setObjectparams('form_name', $this->getId() . '.' . $i);
                        $field->params['this']->setObjectparams('form_ytemplate', $ytemplates);
                        $field->params['this']->setObjectparams('main_id', $main_id);
                        $field->params['form_name'] = $field->getName();
                        $field->params['form_label_type'] = 'html';
                        $field->params['send'] = false;

                        if ($field->getElement(0) == 'be_manager_relation') {
                            $field->params['main_table'] = $field->getElement('table');
                            $field->setName($field->getElement('field'));
                        }
                        $field->setValue(null);
                        $field->setId('{{FIELD_ID}}');
                        $field->enterObject();
                        echo strtr($field->params['form_output'][$field->getId()], ["\n" => '', "\r" => '', "'" => "\'"]);
                        ?></td>\
                    <?php endforeach ?>\
                    <td class="delete-row"><a class="btn btn-xs btn-delete" href="javascript:void(0)"><i class="rex-icon rex-icon-delete"></i> <?php echo rex_i18n::msg('yform_delete') ?></a></td>\
                ';

                be_table_cnt++;
                // set new row field ids
                row_html = row_html.replace(new RegExp('{{FIELD_ID}}', 'g'), be_table_cnt);

                for (var i in regexp) {
                    row_html = row_html.replace(regexp[i], '$1' + be_table_cnt + '<?= $i ?>');
                }
                tr.html(row_html);

                // replace be medialist
                tr.find('select[id^="REX_MEDIALIST_"]').each(function () {
                    var $select = $(this),
                        $input = $select.parent().children('input:first'),
                        id = $select.prop('id').replace('REX_MEDIALIST_SELECT_', '');

                    $input.prop('id', 'REX_MEDIALIST_' + id);
                });


                $table.find('tbody').append(tr);
                $(document).trigger('be_table:row-added', [tr]);
                return false;
            });

            wrapper.on('click', '.btn-delete', function () {
                var tr = jQuery(this).closest('tr');
                tr.fadeOut('normal', function () {
                    $(document).trigger('be_table:before-row-remove', [tr]);
                    tr.remove();
                    $(document).trigger('be_table:row-removed');
                })
                return false;
            });
        })();
    </script>
    <?php echo $notice ?>
</div>
