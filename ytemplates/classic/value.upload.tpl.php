<p class="<?php echo $this->getHTMLClass() ?> formlabel-<?php echo $this->getName() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="text <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" >
        <?php echo $this->getLabel() ?>
    </label>

    <input class="upload <?php echo $this->getWarningClass() ?>" id="<?php echo $this->getFieldId() ?>" name="file_<?php echo md5($this->getFieldName('file')) ?>" type="file" />
</p>
<?php

$value = $this->getValue();
if ($value != "") {
    $values = explode("_",$value,2);
    if (count($values) == 2) {
        echo '<input type="hidden" name="'.$this->getFieldName().'" value="'.$values[0].'" />';

        $a_a = '';
        $a_e = '';

        if (rex::isBackend()) {
            $a_a = '<a href="'.$_SERVER["REQUEST_URI"].'&rex_upload_downloadfile='.urlencode($this->getValue()).'">';
            $a_e = '</a>';
        }

        echo '
<p class="formcheckbox formlabel-'.$this->getName('checkbox').'" id="'.$this->getHTMLId('checkbox').'">
    <input type="checkbox" class="checkbox" name="'.$this->getFieldName('delete').'" id="'.$this->getFieldId('delete').'" value="1" />
    <label class="checkbox" for="'.$this->getFieldId('delete').'" >'.$this->tmp_messages["delete_file"].' "'.$a_a.htmlspecialchars($values[1]).$a_e.'"</label>
</p>';



    }
}

?>
