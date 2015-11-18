<?php if ($option == 'open'): ?>
    <fieldset class="<?php echo $this->getHTMLClass(), ' ', $this->getElement(3) ?>" id="<?php echo $this->getHTMLId() ?>">
        <?php if ($this->getLabel()): ?>
            <legend id="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></legend>
        <?php endif ?>
<?php elseif ($option == 'close'): ?>
    </fieldset>
<?php endif ?>
