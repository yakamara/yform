<?php

/**
 * @var \Redaxo\Core\View\Fragment $this
 * @psalm-scope-this \Redaxo\Core\View\Fragment
 */

?><div class="dropdown yform-dropdown">
    <button class="btn btn-xs btn-default dropdown-toggle" type="button" data-toggle="dropdown">
        <?= \Redaxo\Core\Translation\I18n::msg('yform_function_button') ?>
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <?php
        foreach ($this->buttons ?? [] as $button) {
            echo '<li>' . $button . '</li>'; //  class="small"
        }
        ?>
    </ul>
</div>
