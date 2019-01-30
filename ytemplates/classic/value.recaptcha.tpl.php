<?php
if ($this->getElement(3)) {
    echo '<script src="//www.google.com/recaptcha/api.js" async defer></script>';
}

?>
<div id="<?php echo $this->getHTMLId() ?>" class="g-recaptcha <?php echo $this->getWarningClass(); ?>" data-sitekey="<?php echo $this->getElement(2); ?>"></div>