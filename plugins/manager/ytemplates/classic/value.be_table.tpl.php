<div class="yform-element <?php echo $this->getHTMLClass() ?>" id="<?php echo $this->getHTMLId() ?>">
    <p class="formtable <?php echo $this->getWarningClass() ?>">
        <label class="table <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>
        <a href="javascript:void(0);">+ <?php echo rex_i18n::msg('add_row') ?></a>
    </p>

    <table class="rex-table rex-yform-be-table" id="yform_table<?php echo $this->getId() ?>">
        <tr>
            <?php foreach ($columns as $column): ?>
                <th><?php echo $column ?></th>
            <?php endforeach ?>
            <th></th>
        </tr>
        <?php foreach ($data as $row): ?>
            <tr>
                <?php foreach ($row as $i => $column): ?>
                    <td><input type="text" name="v[<?php echo $this->getId() ?>][<?php echo $i ?>][]" value="<?php echo $column ?>" /></td>
                <?php endforeach ?>
                <td><a href="javascript:void(0)">- <?php echo rex_i18n::msg('yform_delete') ?></a></td>
            </tr>
        <?php endforeach ?>
    </table>

    <script type="text/javascript">
        (function () {
            var wrapper = jQuery('#<?php echo $this->getHTMLId() ?>');

            wrapper.find('> p > a').click(function () {
                wrapper.find('table').append('\
                    <tr>\
                        <?php foreach ($columns as $i => $column): ?>\
                            <td><input type="text" name="v[<?php echo $this->getId() ?>][<?php echo $i ?>][]" value="" /></td>\
                        <?php endforeach ?>\
                        <td><a href="javascript:void(0)">- <?php echo rex_i18n::msg('yform_delete') ?></a></td>\
                    </tr>\
                ');
                return false;
            });

            wrapper.on('click', 'table tr a', function () {
                var tr = jQuery(this).closest('tr');
                tr.fadeOut('normal', function () {
                    tr.remove();
                })
                return false;
            });
        })();
    </script>
</div>
