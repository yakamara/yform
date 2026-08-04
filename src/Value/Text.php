<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Manager\Query;

use function Redaxo\Core\View\escape;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('text')]
class Text extends AbstractValue
{
    public function enterObject()
    {
        $this->setValue((string) $this->getValue());

        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput() || !$this->isViewable()) {
        }

        $templateParams = [];
        $templateParams['prepend'] = $this->getElement('prepend');
        $templateParams['append'] = $this->getElement('append');
        if (!$this->isEditable()) {
            $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
            $attributes['readonly'] = 'readonly';
            $this->setElement('attributes', json_encode($attributes));
            $this->params['form_output'][$this->getId()] = $this->parse(['value.text-view.tpl.php', 'view', 'text'], $templateParams);
        } else {
            $this->params['form_output'][$this->getId()] = $this->parse('text', $templateParams);
        }
    }

    public function getDescription(): string
    {
        return 'text|name|label|defaultwert|[no_db]|[attributes]|[notice]|[prepend]|[append]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'text',
            'values' => [
                'name' => ['type' => 'name',    'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text',    'label' => I18n::msg('yform_values_text_default')],
                'no_db' => ['type' => 'no_db',   'label' => I18n::msg('yform_values_defaults_table'),  'default' => 0],
                'attributes' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_attributes'), 'notice' => I18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_notice')],
                'prepend' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_prepend')],
                'append' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_append')],
            ],
            'description' => I18n::msg('yform_values_text_description'),
            'db_type' => ['varchar(191)', 'text'],
            'famous' => true,
            'hooks' => [
                'preDefault' => static function (Field $field) {
                    return $field->getElement('default');
                },
            ],
        ];
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel(), 'notice' => I18n::msg('yform_search_defaults_wildcard_notice')]);
    }

    public static function getSearchFilter($params)
    {
        $value = trim($params['value']);
        /** @var Query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();

        // whereNull()/whereNotNull(), not where($field, null): the latter builds
        // `= NULL`, which is never true — so "(empty)" never matched a NULL cell.
        // REDAXO 6 makes yform's generated columns nullable, so that is the common case.
        if ('(empty)' == $value) {
            return $query->whereNested(static function (Query $query) use ($field) {
                $query
                    ->where($field, '')
                    ->whereNull($field)
                ;
            }, 'OR');
        }
        if ('!(empty)' == $value) {
            return $query->whereNested(static function (Query $query) use ($field) {
                $query
                    ->where($field, '', '<>')
                    ->whereNotNull($field)
                ;
            }, 'AND');
        }

        $invertWhere = false;
        if ('!' === substr($value, 0, 1)) {
            $invertWhere = true;
            $value = substr($value, 1);
        }

        $pos = strpos($value, '*');
        if (false !== $pos) {
            $value = str_replace('%', '\%', $value);
            $value = str_replace('*', '%', $value);
            return $query->where($field, $value, $invertWhere ? 'NOT LIKE' : 'LIKE');
        }

        return $query->where($field, $value, $invertWhere ? '<>' : '=');
    }

    public static function getListValue($params)
    {
        $value = (string) $params['subject'];
        $length = mb_strlen($value);
        if ($length > 100) {
            $value = mb_substr($value, 0, 50) . ' ... ' . mb_substr($value, -50);
        }
        return '<span>' . escape($value) . '</span>';
    }
}
