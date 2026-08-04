<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Manager\Query;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('integer')]
class Integer extends AbstractValue
{
    public function enterObject()
    {
        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ('' === $this->getValue()) {
            $this->setValue(null);
        } else {
            $this->setValue((int) $this->getValue());
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.integer-view.tpl.php', 'view'], ['prepend' => $this->getElement('unit')]);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.integer.tpl.php', 'text'], ['prepend' => $this->getElement('unit')]);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'integer|name|label|defaultwert|[no_db]|[notice]|[unit]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'integer',
            'values' => [
                'name' => ['type' => 'name',    'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text',    'label' => I18n::msg('yform_values_integer_default')],
                'no_db' => ['type' => 'no_db',   'label' => I18n::msg('yform_values_defaults_table'),  'default' => 0],
                'unit' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_unit')],
                'notice' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_notice')],
            ],
            'description' => I18n::msg('yform_values_integer_description'),
            'db_type' => ['int', 'bigint'],
            'db_null' => true,
        ];
    }

    public static function getListValue($params)
    {
        // Cast: the column is nullable and core's Formatter::custom() is typed `: string`.
        return (!empty($params['params']['field']['unit']) && '' != $params['subject'])
            ? $params['params']['field']['unit'] . ' ' . $params['subject']
            : (string) $params['subject'];
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel(), 'notice' => I18n::msg('yform_search_integer_notice'), 'prepend' => $params['field']->getElement('unit')]);
    }

    public static function getSearchFilter($params)
    {
        $value = $params['value'];
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

        // check for range with 'x..y' or 'x-y' patterns
        if (preg_match('/^\s*(-?\d+)\s*(?:\.\.|-)\s*(-?\d+)\s*$/', $value, $match)) {
            $match[1] = (int) $match[1];
            $match[2] = (int) $match[2];
            return $query->whereBetween($field, $match[1], $match[2]);
        }

        // check for comma separated values
        if (preg_match('/^(-?\d+)(?:\s*,\s*(-?\d+))*$/', $value)) {
            $values = array_map('intval', explode(',', $value));
            return $query->whereListContains($field, $values);
        }

        // default case including optional comparator
        preg_match('/^\s*(<|<=|>|>=|<>|!=)?\s*(.*)$/', $value, $match);
        $comparator = $match[1] ?: '=';
        $value = (int) $match[2];

        return $query->where($field, $value, $comparator);
    }
}
