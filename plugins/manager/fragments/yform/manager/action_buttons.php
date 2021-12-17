<?php

/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

?><div class="dropdown yform-dropdown--on-hover">
    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
        <?= \rex_i18n::msg('yform_function_button') ?>
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <?php
        foreach ($this->buttons ?? [] as $button) {
            echo '<li>'.$button.'</li>'; //  class="small"
        }
        ?>
    </ul>
</div>
