<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_article extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if ($this->needsOutput()) {
            $article = new rex_article_content($this->getElement(1));
            $this->params['form_output'][$this->getId()] = $this->parse('value.article.tpl.php', ['article' => $article]);
        }
    }

    public function getDescription()
    {
        return 'article|article_id';
    }
}
