<?php

if ($this->objparams['warning_messages'] || $this->objparams['unique_error']):
    if ($this->objparams['Error-occured']): ?>
        <dl class="<?php echo $this->objparams['error_class'] ?>">
            <dt><?php echo $this->objparams['Error-occured'] ?></dt>
            <dd>
                <ul>
    <?php else: ?>
                <ul class="<?php echo $this->objparams['error_class'] ?>">
    <?php endif; ?>
                    <?php foreach ($this->objparams['warning_messages'] as $k => $v): ?>
                        <li class="el_<?php echo $k ?>"><?php echo rex_i18n::translate($v, null, false) ?></li>
                    <?php endforeach ?>

                    <?php if ($this->objparams['unique_error'] != ''): ?>
                        <li><?php echo rex_i18n::translate(preg_replace("~\\*|:|\\(.*\\)~Usim", '', $this->objparams['unique_error'])) ?></li>
                    <?php endif ?>
                </ul>
    <?php if ($this->objparams['Error-occured']): ?>
            </dd>
        </dl>
    <?php endif;
endif;
