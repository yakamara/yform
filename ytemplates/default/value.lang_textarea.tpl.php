<div class="formtextarea formlangtextarea " id="<?php echo $this->getHTMLId() ?>">
    <p><label class="textarea <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label></p>
    <div class="tabs">
        <ul class="navi-tab">
            <?php foreach ($REX['CLANG'] as $l => $lang): ?>
                <li><a id="tab_a_<?php echo $l ?>" href="#tab_<?php echo $l ?>"><?php echo $lang ?></a></li>
            <?php endforeach ?>
        </ul>
        <?php foreach ($REX['CLANG'] as $l => $lang): ?>
            <p class="tab" id="tab_<?php echo $l ?>">
                <textarea class="textarea <?php echo $this->getWarningClass() ?>" name="<?php echo $this->getFieldName($l) ?>" id="<?php echo $this->getFieldId($l) ?>" cols="80" rows="10"><?php echo htmlspecialchars(stripslashes($text[$l])) ?></textarea>
            </p>
        <?php endforeach ?>
    </div>
</div>
<script type="text/javascript">
    jQuery(function($) {
        var tabContainers = $('#<?php echo $this->getHTMLId() ?> div.tabs > p.tab');

        $('#<?php echo $this->getHTMLId() ?> div.tabs .navi-tab a').click(function () {

            tabContainers.hide().filter(this.hash).show();
            $('#<?php echo $this->getHTMLId() ?> .tabs .navi-tab a').removeClass('active');
            $(this).addClass('active');
            return false;

        }).filter('#tab_a_<?php echo $REX['CUR_CLANG'] ?>').click();

    });
</script>
