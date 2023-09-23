<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_be_link extends rex_yform_value_abstract
{
    public function enterObject()
    {
        static $counter = 0;
        ++$counter;

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.be_link-view.tpl.php', 'value.view.tpl.php'], compact('counter'));
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.be_link.tpl.php', compact('counter'));
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
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',   'label' => rex_i18n::msg('yform_values_defaults_label')],
                'multiple' => ['type' => 'checkbox',   'label' => rex_i18n::msg('yform_values_be_link_multiple')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_be_link_description'),
            'formbuilder' => false,
            'db_type' => ['text', 'varchar(191)', 'int'],
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
            $article = rex_article::get((int) $article_id);
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

    public static function isArticleInUse(rex_extension_point $ep)
    {
        $rexApiCall = rex_request(\rex_api_function::REQ_CALL_PARAM, 'string', '');
        if ('category_delete' == $rexApiCall || 'article_delete' == $rexApiCall) {
            $id = ('category_delete' == $rexApiCall) ? rex_request('category-id', 'int', 0) : rex_request('article_id', 'int', 0);
            $article = \rex_article::get($id);
            if ($article) {
                $sql = \rex_sql::factory();
                $sql->setQuery('SELECT * FROM `' . \rex_yform_manager_field::table() . '` LIMIT 0');

                $columns = $sql->getFieldnames();
                $select = in_array('multiple', $columns) ? ', `multiple`' : '';

                $fields = $sql->getArray('SELECT `table_name`, `name`' . $select . ' FROM `' . \rex_yform_manager_field::table() . '` WHERE `type_id`="value" AND `type_name` IN("be_link")');
                $fields = \rex_extension::registerPoint(new \rex_extension_point('YFORM_ARTICLE_IS_IN_USE', $fields));

                if (count($fields)) {
                    $tables = [];
                    foreach ($fields as $field) {
                        $tableName = $field['table_name'];
                        $condition = $sql->escapeIdentifier((string) $field['name']) . ' = ' . $article->getId();

                        if (isset($field['multiple']) && 1 == $field['multiple']) {
                            $condition = 'FIND_IN_SET(' . $article->getId() . ', ' . $sql->escapeIdentifier((string) $field['name']) . ')';
                        }
                        $tables[$tableName][] = $condition;
                    }
                    $messages = '';
                    foreach ($tables as $tableName => $conditions) {
                        $items = $sql->getArray('SELECT `id` FROM ' . $tableName . ' WHERE ' . implode(' OR ', $conditions));
                        if (count($items)) {
                            foreach ($items as $item) {
                                $sqlData = \rex_sql::factory();
                                $sqlData->setQuery('SELECT `name` FROM `' . \rex_yform_manager_table::table() . '` WHERE `table_name` = "' . $tableName . '"');

                                $url = \rex_url::backendController([
                                    'page' => 'yform/manager/data_edit',
                                    'table_name' => $tableName,
                                    'data_id' => $item['id'],
                                    'func' => 'edit',
                                ]);
                                $messages .= '<li><a href="' . $url . '">' . $sqlData->getValue('name') . ' [id=' . $item['id'] . ']</a></li>';
                            }
                        }
                    }

                    if ('' != $messages) {
                        $_REQUEST[\rex_api_function::REQ_CALL_PARAM] = '';

                        \rex_extension::register('PAGE_TITLE_SHOWN', static function (rex_extension_point $ep) use ($article, $messages) {
                            $warning = $article->isStartArticle() ? \rex_i18n::msg('yform_structure_category_could_not_be_deleted') : \rex_i18n::msg('yform_structure_article_could_not_be_deleted');
                            $warning .= '<br /><ul>' . $messages . '</ul>';
                            $subject = $ep->getSubject();
                            $ep->setSubject(\rex_view::error($warning) . $subject);
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
        /** @var rex_yform_manager_query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();
        return '' == $value ? $query : $query->whereListContains($field, $value);
    }
}
