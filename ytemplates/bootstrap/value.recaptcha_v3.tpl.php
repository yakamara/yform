<?php

if (1 == $loadScript) {
    echo '<script src="https://www.google.com/recaptcha/api.js?render=' . $publicKey . '"></script>';
}

?>
<input id="<?php echo $this->getHTMLId() ?>" type="hidden" name="g-recaptcha-response">
<script>
    grecaptcha.ready(function() {
        var element = $('#<?php echo $this->getHTMLId() ?>')[0];

        var form = element.form;

        $(form).on('submit', function (e) {
            e.preventDefault();

            grecaptcha.execute('<?php echo $publicKey ?>').then(function(token) {
                $('#<?php echo $this->getHTMLId() ?>').val(token);
                form.submit();
            });

            return false;
        });
    });
</script>