<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\ApiFunction\ApiFunction;
use Redaxo\Core\Content\Article;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Message;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Manager\Manager;
use Yakamara\YForm\Manager\Query;
use Yakamara\YForm\Manager\Table\Table;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('be_link')]
class BackendLink extends AbstractValue
{
    public function enterObject()
    {
        static $counter = 0;
        ++$counter;

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.be_link-view.tpl.php', 'view'], compact('counter'));
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('be_link', compact('counter'));
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDB()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'be_link',
            'values' => [
                'name' => ['type' => 'name',   'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',   'label' => I18n::msg('yform_values_defaults_label')],
                'multiple' => ['type' => 'checkbox',   'label' => I18n::msg('yform_values_be_link_multiple')],
                'notice' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_notice')],
            ],
            'description' => I18n::msg('yform_values_be_link_description'),
            'formbuilder' => false,
            'db_type' => ['text', 'varchar(191)', 'int', 'int(10) unsigned'],
        ];
    }

    public static function getListValue($params)
    {
        if ('' == $params['value']) {
            return '-';
        }
        $ids = explode(',', $params['value']);

        $names = [];
        foreach ($ids as $article_id) {
            $article = Article::get((int) $article_id);
            if ($article) {
                $names[] = $article->getValue('name');
            }
        }

        if (0 == count($names)) {
            return '-';
        }
        if (count($names) > 4) {
            $names = array_slice($names, 0, 4);
            $names[] = '...';
        }
        return implode('<br />', $names);
    }

    public static function isArticleInUse(ExtensionPoint $ep)
    {
        $rexApiCall = Request::request(ApiFunction::REQ_CALL_PARAM, 'string', '');
        if ('category_delete' == $rexApiCall || 'article_delete' == $rexApiCall) {
            $id = ('category_delete' == $rexApiCall) ? Request::request('category-id', 'int', 0) : Request::request('article_id', 'int', 0);
            $article = Article::get($id);
            if ($article) {
                $sql = Sql::factory();
                $sql->setQuery('SELECT * FROM `' . Field::table() . '` LIMIT 0');

                $columns = $sql->getFieldnames();
                $select = in_array('multiple', $columns) ? ', `multiple`' : '';

                $fields = $sql->getArray('SELECT `table_name`, `name`' . $select . ' FROM `' . Field::table() . '` WHERE `type_id`="value" AND `type_name` IN("be_link")');
                $fields = Extension::dispatch(new ExtensionPoint('YFORM_ARTICLE_IS_IN_USE', $fields));

                if (count($fields)) {
                    $tables = [];
                    foreach ($fields as $field) {
                        $tableName = $field['table_name'];
                        $condition = $sql->escapeIdentifier((string) $field['name']) . ' = ' . $article->id;

                        if (isset($field['multiple']) && 1 == $field['multiple']) {
                            $condition = 'FIND_IN_SET(' . $article->id . ', ' . $sql->escapeIdentifier((string) $field['name']) . ')';
                        }
                        $tables[$tableName][] = $condition;
                    }
                    $messages = '';
                    foreach ($tables as $tableName => $conditions) {
                        $items = $sql->getArray('SELECT `id` FROM ' . $tableName . ' WHERE ' . implode(' OR ', $conditions));
                        if (count($items)) {
                            foreach ($items as $item) {
                                $sqlData = Sql::factory();
                                $sqlData->setQuery('SELECT `name` FROM `' . Table::table() . '` WHERE `table_name` = "' . $tableName . '"');
                                $url = Manager::url($tableName, $item['id']);
                                $messages .= '<li><a href="' . $url . '">' . $sqlData->getValue('name') . ' [id=' . $item['id'] . ']</a></li>';
                            }
                        }
                    }

                    if ('' != $messages) {
                        $_REQUEST[ApiFunction::REQ_CALL_PARAM] = '';

                        Extension::register('PAGE_TITLE_SHOWN', static function (ExtensionPoint $ep) use ($article, $messages) {
                            $warning = $article->isStartArticle() ? I18n::msg('yform_structure_category_could_not_be_deleted') : I18n::msg('yform_structure_article_could_not_be_deleted');
                            $warning .= '<br /><ul>' . $messages . '</ul>';
                            $subject = $ep->subject;
                            $ep->subject = Message::error($warning) . $subject;
                        });
                    }
                }
            }
        }
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField(
            'be_link',
            [
                'name' => $params['field']->getName(),
                'label' => $params['field']->getLabel(),
            ],
        );
    }

    public static function getSearchFilter($params)
    {
        $value = trim($params['value']);
        /** @var Query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();
        return '' == $value ? $query : $query->whereListContains($field, $value);
    }
}
