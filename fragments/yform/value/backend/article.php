<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$class_group = trim('form-group ' . $yfield->getHTMLClass() . ' ' . $yfield->getWarningClass());
?>
<div class="<?= $class_group ?>" id="<?= $yfield->getHTMLId() ?>">
    <?= $article->getArticle() ?>
</div>
