<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Content\ArticleContent;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('article')]
class Article extends AbstractValue
{
    public function enterObject()
    {
        if ($this->needsOutput()) {
            $article = new ArticleContent($this->getElement(1));
            $this->params['form_output'][$this->getId()] = $this->parse('article', ['article' => $article]);
        }
    }

    public function getDescription(): string
    {
        return 'article|article_id';
    }
}
