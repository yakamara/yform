<div class="dropdown">
    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
        Aktion
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
