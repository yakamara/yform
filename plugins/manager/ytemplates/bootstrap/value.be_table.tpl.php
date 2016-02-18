<?php
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
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th><?php echo $column ?></th>
            <?php endforeach ?>
            <th class="rex-table-action"><a class="btn btn-xs btn-default" id="<?= $this->getHTMLId() ?>-add-row" href="javascript:void(0);"><i class="rex-icon rex-icon-add"></i> <?php echo rex_i18n::msg('yform_add_row') ?></a></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <?php foreach ($row as $i => $column): ?>
                    <td><input class="form-control" type="text" name="v[<?php echo $this->getId() ?>][<?php echo $i ?>][]" value="<?php echo $column ?>" /></td>
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
                            <td><input class="form-control" type="text" name="v[<?php echo $this->getId() ?>][<?php echo $i ?>][]" value="" /></td>\
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
