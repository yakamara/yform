<?php

/**
 * @var rex_yform_value_abstract $this
 * @psalm-scope-this rex_yform_value_abstract
 * @var rex_article_content $article
 */

$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <?= $article->getArticle() ?>
</div>
