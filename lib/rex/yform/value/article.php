<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_article extends rex_yform_value_abstract
{

    function enterObject()
    {
        $article = rex_article::get($this->getElement(1));
        if ( $article ) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.article.tpl.php', array('article' => $article));
        }
    }

    function getDescription()
    {
        return 'article -> Beispiel: article|article_id';
    }

}
