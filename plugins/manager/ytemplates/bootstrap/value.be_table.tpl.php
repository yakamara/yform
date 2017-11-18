<?php
$class_group = trim('form-group yform-element ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$notice = [];
if ($this->getElement('notice') != '') {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th><?php echo htmlspecialchars($column['label']) ?></th>
            <?php endforeach ?>
            <th class="rex-table-action"><a class="btn btn-xs btn-default" id="<?= $this->getHTMLId() ?>-add-row" href="javascript:void(0);"><i class="rex-icon rex-icon-add"></i> <?php echo rex_i18n::msg('yform_add_row') ?></a></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <?php foreach ($columns as $i => $column): ?>
                    <td class="be-value-input">
                        <?php
                            $field = $column['field'];
                            $field->setValue(htmlspecialchars($row[$i] ?: ''));
                            $field->params['this']->setObjectparams('form_name', $this->getId() .'.'. $i);
                            $field->enterObject();
                            echo $field->params['form_output'][$field->getId()]
                        ?>
                    </td>
                <?php endforeach ?>
                <td><a class="btn btn-xs btn-delete" href="javascript:void(0)"><i class="rex-icon rex-icon-delete"></i> <?php echo rex_i18n::msg('yform_delete') ?></a></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <script type="text/javascript">
        (function () {
            var wrapper = jQuery('#<?php echo $this->getHTMLId() ?>'),
                be_table_cnt = 0;

            wrapper.find('#<?= $this->getHTMLId() ?>-add-row').click(function () {
                var tr = $('<tr/>'),
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
                            <td class="be-value-input"><?php
                        $field = $columns[$i]['field'];
                        $field->setValue(null);
                        $field->params['this']->setObjectparams('form_name', $this->getId() . '.' . $i);
                        $field->enterObject();
                        echo strtr($field->params['form_output'][$field->getId()], ["\n" => '', "\r" => '', "'" => "\'"]);
                        ?></td>\
                    <?php endforeach ?>\
                    <td><a class="btn btn-xs btn-delete" href="javascript:void(0)"><i class="rex-icon rex-icon-delete"></i> <?php echo rex_i18n::msg('yform_delete') ?></a></td>\
                ';

                be_table_cnt++;

                for (var i in regexp) {
                    row_html = row_html.replace(regexp[i], '$1'+ be_table_cnt +'<?= $i ?>');
                }
                tr.html(row_html);

                // replace be medialist
                tr.find('select[id^="REX_MEDIALIST_"]').each(function() {
                    var $select = $(this),
                        $input  = $select.parent().children('input:first'),
                        id = $select.prop('id').replace('REX_MEDIALIST_SELECT_', '');

                    $input.prop('id', 'REX_MEDIALIST_'+ id);
                });


                $(this).closest('table').find('tbody').append(tr);
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
