<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\MediaPool\Media;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Manager\Manager;
use Yakamara\YForm\Manager\Table\Table;

use function Redaxo\Core\View\escape;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('be_media')]
class BackendMedia extends AbstractValue
{
    public function enterObject()
    {
        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        if ('' != $this->getValue()) {
            $medias = [];
            foreach (explode(',', $this->getValue()) as $media) {
                if (Media::get($media)) {
                    $medias[] = $media;
                }
            }
            $this->setValue(implode(',', $medias));
        }

        $types = $this->getElement('types') ?? '';
        // to be in line with upload field
        if ('*' == $types) {
            $types = '';
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(
                    ['view', 'value.be_media-view.tpl.php'],
                    ['value' => explode(',', $this->getValue()), 'types' => $types],
                );
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('be_media', compact('types'));
            }
        }

        $this->params['value_pool']['email'][$this->getElement(1)] = $this->getValue();
        if ($this->saveInDB()) {
            $this->params['value_pool']['sql'][$this->getElement(1)] = $this->getValue();
        }
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'be_media',
            'values' => [
                'name' => ['type' => 'name',   'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_label')],
                'preview' => ['type' => 'checkbox',   'label' => I18n::msg('yform_values_be_media_preview')],
                'multiple' => ['type' => 'checkbox',   'label' => I18n::msg('yform_values_be_media_multiple')],
                'category' => ['type' => 'text',   'label' => I18n::msg('yform_values_be_media_category')],
                'types' => ['type' => 'text',   'label' => I18n::msg('yform_values_be_media_types'),   'notice' => I18n::msg('yform_values_be_media_types_notice'), 'default' => '*'],
                'notice' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_notice')],
            ],
            'description' => I18n::msg('yform_values_be_media_description'),
            'formbuilder' => false,
            'db_type' => ['text'],
        ];
    }

    public static function getListValue($params)
    {
        $files = explode(',', $params['subject']);

        $return = [];
        if (1 == count($files)) {
            $filename = $params['subject'];
            if (mb_strlen($params['subject']) > 16) {
                $filename = mb_substr($params['subject'], 0, 6) . ' ... ' . mb_substr($params['subject'], -6);
            }
            $return[] = '<span style="white-space:nowrap;" title="' . escape($params['subject']) . '">' . $filename . '</span>';
        } else {
            foreach ($files as $file) {
                $filename = $file;
                if (mb_strlen($file) > 16) {
                    $filename = mb_substr($file, 0, 6) . ' ... ' . mb_substr($file, -6) . '</span>';
                }
                $return[] = '<span style="white-space:nowrap;" title="' . escape($file) . '">' . $filename . '</span>';
            }
        }

        if (4 < count($return)) {
            $return = array_merge(array_slice($return, 2, 2), ['...'], array_slice($return, -2, 2));
        }

        return implode('<br />', $return);
    }

    public static function getSearchField($params)
    {
        Text::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return Text::getSearchFilter($params);
    }

    public static function isMediaInUse(ExtensionPoint $ep)
    {
        $params = $ep->getParams();
        $warning = $ep->subject;

        $sql = Sql::factory();
        $sql->setQuery('SELECT * FROM `' . Field::table() . '` LIMIT 0');

        $columns = $sql->getFieldnames();
        $select = in_array('multiple', $columns) ? ', `multiple`' : '';

        $fields = $sql->getArray('SELECT `table_name`, `name`' . $select . ' FROM `' . Field::table() . '` WHERE `type_id`="value" AND `type_name` IN("be_media")');
        $fields = Extension::dispatch(new ExtensionPoint('YFORM_MEDIA_IS_IN_USE', $fields));

        if (!count($fields)) {
            return $warning;
        }

        $tables = [];
        $escapedFilename = $sql->escape($params['filename']);
        foreach ($fields as $field) {
            $tableName = $field['table_name'];
            $condition = $sql->escapeIdentifier((string) $field['name']) . ' = ' . $escapedFilename;

            if (isset($field['multiple']) && 1 == $field['multiple']) {
                $condition = 'FIND_IN_SET(' . $escapedFilename . ', ' . $sql->escapeIdentifier((string) $field['name']) . ')';
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
                    $editUrl = Manager::url($tableName, $item['id']);
                    $messages .= '<li><a href="javascript:openPage(\'' . $editUrl . '\')">' . $sqlData->getValue('name') . ' [id=' . $item['id'] . ']</a></li>';
                }
            }
        }

        if ('' != $messages) {
            $warning[] = 'Tabelle<br /><ul>' . $messages . '</ul>';
        }

        return $warning;
    }
}
