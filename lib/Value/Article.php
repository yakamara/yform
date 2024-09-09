<?php

namespace Yakamara\YForm\Value;

use rex_article_content;

class Article extends AbstractValue
{
    public function enterObject()
    {
        if ($this->needsOutput()) {
            $article = new rex_article_content($this->getElement(1));
            $this->params['form_output'][$this->getId()] = $this->parse('value.article.tpl.php', ['article' => $article]);
        }
    }

    public function getDescription(): string
    {
        return 'article|article_id';
    }
}
