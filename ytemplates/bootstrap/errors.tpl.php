<div class="alert alert-danger">

<?php
if ($this->objparams['warning_messages'] || $this->objparams['unique_error']):
    if ($this->objparams['Error-occured']): ?>
        <dl class="dl-horizontal">
            <dt><?php echo $this->objparams['Error-occured'] ?></dt>
            <dd>
                <ul>
    <?php else: ?>
                <ul>
    <?php endif; ?>
                    <?php

    $warning_messages = [];
    foreach ($this->objparams['warning_messages'] as $k => $v) {
        $warning_messages[rex_i18n::translate("$v", null)] = '<li>'.rex_i18n::translate("$v", null).'</li>';
    }
    if (count($warning_messages)>0) {
        echo implode('', $warning_messages);
    }

    if ($this->objparams['unique_error'] != '') {
        echo '<li>'.rex_i18n::translate(preg_replace("~\\*|:|\\(.*\\)~Usim", '', $this->objparams['unique_error'])).'</li>';
    }

    ?>
                </ul>
    <?php if ($this->objparams['Error-occured']): ?>
            </dd>
        </dl>
    <?php endif;
endif;
?>
</div>