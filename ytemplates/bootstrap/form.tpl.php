<div id="<?php echo $this->objparams['form_wrap_id'] ?>" class="<?php echo $this->objparams['form_wrap_class'] ?>">

    <form action="<?php echo $this->objparams['form_action'] ?>" method="<?php echo $this->objparams['form_method'] ?>" id="<?php echo  $this->objparams['form_name'] ?>" class="<?php echo $this->objparams['form_class'] ?>" enctype="multipart/form-data">

        <?php
        if (!$this->objparams['hide_top_warning_messages']){
        if ($this->objparams['warning_messages'] || $this->objparams['unique_error']){
            echo $this->parse('errors.tpl.php');
        }}
        ?>

        <?php foreach ($this->objparams['form_output'] as $field):
            echo $field;
        endforeach ?>

        <?php for ($i = 0; $i < $this->objparams['fieldsets_opened']; $i++):
            echo $this->parse('value.fieldset.tpl.php', array('option' => 'close'));
        endfor ?>

        <?php foreach ($this->objparams['form_hiddenfields'] as $k => $v): ?>
            <?php if (is_array($v)): foreach ($v as $l => $w): ?>
                <input type="hidden" name="<?php echo $k, '[', $l, ']' ?>" value="<?php echo htmlspecialchars($w) ?>" />
            <?php endforeach; else: ?>
                <input type="hidden" name="<?php echo $k ?>" value="<?php echo htmlspecialchars($v) ?>" />
            <?php endif; ?>
        <?php endforeach ?>
    </form>
</div>
