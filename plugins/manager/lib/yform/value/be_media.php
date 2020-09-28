<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_be_media extends rex_yform_value_abstract
{
    public function enterObject()
    {
        static $counter = 0;
        ++$counter;

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.be_media.tpl.php', compact('counter'));
        }

        $this->params['value_pool']['email'][$this->getElement(1)] = $this->getValue();
        $this->params['value_pool']['sql'][$this->getElement(1)] = $this->getValue();
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'be_media',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'preview' => ['type' => 'checkbox',   'label' => rex_i18n::msg('yform_values_be_media_preview')],
                'multiple' => ['type' => 'checkbox',   'label' => rex_i18n::msg('yform_values_be_media_multiple')],
                'category' => ['type' => 'text',   'label' => rex_i18n::msg('yform_values_be_media_category')],
                'types' => ['type' => 'text',   'label' => rex_i18n::msg('yform_values_be_media_types'),   'notice' => rex_i18n::msg('yform_values_be_media_types_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_be_media_description'),
            'formbuilder' => false,
            'db_type' => ['text'],
        ];
    }

    public static function getListValue($params)
    {
        $files = explode(',', $params['subject']);

        if (1 == count($files)) {
            $filename = $params['subject'];
            if (strlen($params['subject']) > 16) {
                $filename = mb_substr($params['subject'], 0, 6) . ' ... ' . mb_substr($params['subject'], -6);
            }
            $return[] = '<span style="white-space:nowrap;" title="' . rex_escape($params['subject']) . '">' . $filename . '</span>';
        } else {
            foreach ($files as $file) {
                $filename = $file;
                if (strlen($file) > 16) {
                    $filename = mb_substr($file, 0, 6) . ' ... ' . mb_substr($file, -6) . '</span>';
                }
                $return[] = '<span style="white-space:nowrap;" title="' . htmlspecialchars($file) . '">' . $filename . '</span>';
            }
        }

        return implode('<br />', $return);
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel()]);
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();
        $value = $params['value'];
        $field = $params['field']->getName();

        if ('(empty)' == $value) {
            return ' (' . $sql->escapeIdentifier($field) . ' = "" or ' . $sql->escapeIdentifier($field) . ' IS NULL) ';
        }
        if ('!(empty)' == $value) {
            return ' (' . $sql->escapeIdentifier($field) . ' <> "" and ' . $sql->escapeIdentifier($field) . ' IS NOT NULL) ';
        }

        $pos = strpos($value, '*');
        if (false !== $pos) {
            $value = str_replace('%', '\%', $value);
            $value = str_replace('*', '%', $value);
            return $sql->escapeIdentifier($field) . ' LIKE ' . $sql->escape($value);
        }
        return $sql->escapeIdentifier($field) . ' = ' . $sql->escape($value);
    }

    public static function isMediaInUse(\rex_extension_point $ep)
    {
        $params = $ep->getParams();
        $warning = $ep->getSubject();

        $sql = \rex_sql::factory();
        $sql->setQuery('SELECT * FROM `'.\rex_yform_manager_field::table().'` LIMIT 0');

        $columns = $sql->getFieldnames();
        $select = in_array('multiple', $columns) ? ', `multiple`' : '';

        $fields = $sql->getArray('SELECT `table_name`, `name`'.$select.' FROM `'.\rex_yform_manager_field::table().'` WHERE `type_id`="value" AND `type_name` IN("be_media","mediafile")');
        $fields = \rex_extension::registerPoint(new \rex_extension_point('YFORM_MEDIA_IS_IN_USE', $fields));

        if (!count($fields)) {
            return $warning;
        }

        $tables = [];
        $escapedFilename = $sql->escape($params['filename']);
        foreach ($fields as $field) {
            $tableName = $field['table_name'];
            $condition = $sql->escapeIdentifier($field['name']).' = '.$escapedFilename;

            if (isset($field['multiple']) && 1 == $field['multiple']) {
                $condition = 'FIND_IN_SET('.$escapedFilename.', '.$sql->escapeIdentifier($field['name']).')';
            }
            $tables[$tableName][] = $condition;
        }

        $messages = '';
        foreach ($tables as $tableName => $conditions) {
            $items = $sql->getArray('SELECT `id` FROM '.$tableName.' WHERE '.implode(' OR ', $conditions));
            if (count($items)) {
                foreach ($items as $item) {
                    $sqlData = \rex_sql::factory();
                    $sqlData->setQuery('SELECT `name` FROM `'.\rex_yform_manager_table::table().'` WHERE `table_name` = "'.$tableName.'"');

                    $messages .= '<li><a href="javascript:openPage(\'index.php?page=yform/manager/data_edit&amp;table_name='.$tableName.'&amp;data_id='.$item['id'].'&amp;func=edit\')">'.$sqlData->getValue('name').' [id='.$item['id'].']</a></li>';
                }
            }
        }

        if ('' != $messages) {
            $warning[] = 'Tabelle<br /><ul>'.$messages.'</ul>';
        }

        return $warning;
    }
}
