<?php

use Yakamara\YForm\Value\AbstractValue;

/**
 * @var AbstractValue $this
 * @var rex_article_content $article
 */
$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <?= $article->getArticle() ?>
</div>
