<div id="<?= $this->getHTMLId() ?>">
        <label for="<?= $this->getFieldId() ?>"><?= $this->getLabel() ?></label>
        <input id="<?= $this->getFieldId() ?>" name="<?= $this->getFieldId() ?>" type="email" autocomplete="off" tabindex="-1">
        <input id="<?= $this->getFieldId() ?>_microtime" name="<?= $this->getFieldId() ?>_microtime" type="hidden" value="<?= microtime(true) ?>" readonly="readonly"  tabindex="-1">
<style>
[id="<?=$this->getHTMLId() ?>"] {
    display: none; 
}
</style>
</div>
