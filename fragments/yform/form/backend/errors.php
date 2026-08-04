<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\YForm $yform
 */
extract($this->getVariables());
?><div class="alert alert-danger">

<?php
if ($yform->objparams['warning_messages'] || $yform->objparams['unique_error']):
    if ($yform->objparams['Error-occured']):
        if($yform->objparams['warning_intro']) { ?>
            <p><?= $yform->objparams['warning_intro'] ?></p>
        <?php } ?>
        <dl class="dl-horizontal">
            <dt><?= $yform->objparams['Error-occured'] ?></dt>
            <dd>
                <ul>
    <?php else: ?>
                <ul>
    <?php endif ?>
                    <?php

    $warning_messages = [];
    foreach ($yform->objparams['warning_messages'] as $k => $v) {
        $message = \Redaxo\Core\Translation\I18n::translate("$v", false);
        /** @phpstan-ignore-next-line */
        if ('' == $message && isset($yform->objparams['values'][$k])) {
            $message = \Redaxo\Core\Addon\Addon::get('yform')->i18n('yform_values_message_is_missing', $yform->objparams['values'][$k]->getLabel(), $yform->objparams['values'][$k]->getName());
        }
        $warning_messages[\Redaxo\Core\Translation\I18n::translate("$v", false)] = '<li>' . $message . '</li>';
    }
    if (count($warning_messages) > 0) {
        echo implode('', $warning_messages);
    }

    if ('' != $yform->objparams['unique_error']) {
        echo '<li>' . \Redaxo\Core\Translation\I18n::translate(preg_replace('~\\*|:|\\(.*\\)~Usim', '', $yform->objparams['unique_error'])) . '</li>';
    }

    ?>
                </ul>
    <?php if ($yform->objparams['Error-occured']): ?>
            </dd>
        </dl>
    <?php endif;
endif;
?>
</div>
