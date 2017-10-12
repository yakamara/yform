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
            var wrapper = jQuery('#<?php echo $this->getHTMLId() ?>');

            wrapper.find('#<?= $this->getHTMLId() ?>-add-row').click(function () {
                $(this).closest('table').find('tbody').append('\
                    <tr>\
                        <?php foreach ($columns as $i => $column): ?>\
                            <td class="be-value-input"><?php
                                $field = $columns[$i]['field'];
                                $field->setValue(null);
                                $field->params['this']->setObjectparams('form_name', $this->getId() .'.'. $i);
                                $field->enterObject();
                                echo strtr($field->params['form_output'][$field->getId()], ["\n" => '', "\r" => '', "'" => "\'"]);
                            ?></td>\
                        <?php endforeach ?>\
                        <td><a class="btn btn-xs btn-delete" href="javascript:void(0)"><i class="rex-icon rex-icon-delete"></i> <?php echo rex_i18n::msg('yform_delete') ?></a></td>\
                    </tr>\
                ');
                return false;
            });

            wrapper.on('click', '.btn-delete', function () {
                var tr = jQuery(this).closest('tr');
                tr.fadeOut('normal', function () {
                    tr.remove();
                })
                return false;
            });
        })();
    </script>
    <?php echo $notice ?>
</div>
