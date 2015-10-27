<?php
/*
 * Use
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * objparams|form_skin|bootstrap|runtime
 * 
 * text|key|Label
 * 
 * objparams|form_skin|bootstrap-checkbox-radio|runtime
 * checkbox|key|Label|0,1|0|no_db
 * objparams|form_skin|bootstrap|runtime
 *
 * 
 */
    $value = isset($value) ? $value : 1;
    
    $class_group = trim('form-group ' . $this->getWarningClass());
?>
<div class="checkbox" id="<?php echo $this->getHTMLId() ?>">
    <label>
        <input type="checkbox" id="<?php echo $this->getFieldId() ?>" name="<?php echo $this->getFieldName() ?>" value="<?php echo $value ?>"<?php echo $this->getValue() == $value ? ' checked="checked"' : '' ?> />
        <span class="checkbox-label"><?php echo $this->getLabel() ?></span>
    </label>
</div>