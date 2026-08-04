<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Manager\Query;
use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Manager\Field;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('number')]
class Number extends AbstractValue
{
    public function enterObject()
    {
        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ('' === $this->getValue()) {
            $this->setValue(null);
        } else {
            $this->setValue(self::normalizeDecimal($this->getValue()));
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput() || !$this->isViewable()) {
            return;
        }

        if (!$this->isEditable()) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.number-view.tpl.php', 'value.integer-view.tpl.php', 'view'],
                ['prepend' => $this->getElement('unit')],
            );
        } else {
            $type = 'text';
            if ('input:number' == $this->getElement('widget')) {
                $type = 'number';
            }
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.number.tpl.php', 'value.integer.tpl.php', 'text'],
                ['prepend' => $this->getElement('unit'), 'type' => $type],
            );
        }
    }

    public function getDescription(): string
    {
        return 'number|name|label|precision|scale|defaultwert|[no_db]|[unit]|[notice]|[attributes]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'number',
            'values' => [
                'name' => ['type' => 'name',    'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_label')],
                'precision' => ['type' => 'integer', 'label' => I18n::msg('yform_values_number_precision'), 'default' => '10'],
                'scale' => ['type' => 'integer', 'label' => I18n::msg('yform_values_number_scale'), 'default' => '2'],
                'default' => ['type' => 'text',    'label' => I18n::msg('yform_values_number_default')],
                'no_db' => ['type' => 'no_db',   'label' => I18n::msg('yform_values_defaults_table'),  'default' => 0],
                'widget' => ['type' => 'choice', 'label' => I18n::msg('yform_values_defaults_widgets'), 'choices' => ['input:text' => 'input:text', 'input:number' => 'input:number'], 'default' => 'input:text'],
                'unit' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_unit')],
                'notice' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_notice')],
                'attributes' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_attributes'), 'notice' => I18n::msg('yform_values_defaults_attributes_notice')],
            ],
            'validates' => [
                ['type' => ['name' => 'precision', 'type' => 'integer', 'message' => I18n::msg('yform_values_number_error_precision', '1', '65'), 'not_required' => false]],
                ['type' => ['name' => 'scale', 'type' => 'integer', 'message' => I18n::msg('yform_values_number_error_scale', '0', '30'), 'not_required' => false]],
                ['compare' => ['name' => 'scale', 'name2' => 'precision', 'message' => I18n::msg('yform_values_number_error_compare'), 'compare_type' => '>']],
                ['intfromto' => ['name' => 'precision', 'from' => '1', 'to' => '65', 'message' => I18n::msg('yform_values_number_error_precision', '1', '65')]],
                ['intfromto' => ['name' => 'scale', 'from' => '0', 'to' => '30', 'message' => I18n::msg('yform_values_number_error_scale', '0', '30')]],
            ],
            'description' => I18n::msg('yform_values_number_description'),
            'db_type' => ['DECIMAL({precision},{scale})'],
            'hooks' => [
                'preCreate' => static function (Field $field, $db_type) {
                    $db_type = str_replace('{precision}', (string) ($field->getElement('precision') ?? 6), $db_type);
                    $db_type = str_replace('{scale}', (string) ($field->getElement('scale') ?? 2), $db_type);
                    return $db_type;
                },
            ],
            'db_null' => true,
        ];
    }

    public static function getSearchField($params)
    {
        Integer::getSearchField($params);
    }

    /**
     * Normalises user input to a decimal literal MySQL accepts.
     *
     * `integer` casts its input with `(int)` for the same reason; `number` had no
     * equivalent, so a German "2,50" reached the DECIMAL column verbatim. Under
     * REDAXO 5's `SQL_MODE=""` MySQL truncated that to 2.00, REDAXO 6 runs strict
     * and rejects the whole statement — the record was lost with an SQL error
     * instead of a field message.
     *
     * Accepts "2,50", "1.234,56" and "1,234.56"; anything that is not a number
     * after normalisation becomes null (an empty field), matching how `integer`
     * turns junk into 0. Use `validate|type|<field>|numeric` to reject it loudly.
     */
    public static function normalizeDecimal(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $value = trim((string) $value);
        if ('' === $value) {
            return null;
        }
        if (is_numeric($value)) {
            return $value;
        }

        $sign = str_starts_with($value, '-') ? '-' : '';
        $digits = preg_replace('/[^0-9.,]/', '', $value) ?? '';

        // The right-most separator is the decimal point; everything else groups thousands.
        $lastComma = strrpos($digits, ',');
        $lastDot = strrpos($digits, '.');
        $decimalPos = max(false === $lastComma ? -1 : $lastComma, false === $lastDot ? -1 : $lastDot);

        if ($decimalPos < 0) {
            $normalized = $digits;
        } else {
            $integerPart = preg_replace('/[.,]/', '', substr($digits, 0, $decimalPos)) ?? '';
            $fraction = preg_replace('/[.,]/', '', substr($digits, $decimalPos + 1)) ?? '';
            $normalized = '' === $fraction ? $integerPart : $integerPart . '.' . $fraction;
        }

        $normalized = $sign . $normalized;

        return is_numeric($normalized) ? $normalized : null;
    }

    /**
     * Same grammar as `integer` — "(empty)", "x..y" ranges, comma lists and an
     * optional comparator — but on a DECIMAL column, so the operands stay floats.
     * Delegating to `integer` cast them with `(int)`, which made a search for
     * "19.99" look for 19 and return nothing.
     */
    public static function getSearchFilter($params)
    {
        $value = trim((string) $params['value']);
        /** @var Query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();

        if ('(empty)' === $value || '!(empty)' === $value) {
            return Integer::getSearchFilter($params);
        }

        $number = '-?\d+(?:[.,]\d+)?';

        // range: "x..y" or "x-y"
        if (preg_match('/^\s*(' . $number . ')\s*(?:\.\.|(?<=[\d\s])-)\s*(' . $number . ')\s*$/', $value, $match)) {
            return $query->whereBetween($field, (float) self::normalizeDecimal($match[1]), (float) self::normalizeDecimal($match[2]));
        }

        // Comma-separated list. "19,99" is ambiguous — it reads as one German decimal
        // or as two values. A single comma between digits wins as the decimal point;
        // a second comma, a dot anywhere, or a sign after the comma makes it a list.
        $isList = substr_count($value, ',') > 1
            || (str_contains($value, ',') && str_contains($value, '.'))
            || preg_match('/,\s*[+-]/', $value);
        if ($isList && preg_match('/^\s*' . $number . '(?:\s*,\s*' . $number . ')+\s*$/', $value)) {
            // IN(), not whereListContains(): that one casts every operand with intval().
            $values = array_map(static fn ($v) => (float) self::normalizeDecimal($v), explode(',', $value));
            return $query->where($field, $values, 'IN');
        }

        preg_match('/^\s*(<|<=|>|>=|<>|!=)?\s*(.*)$/', $value, $match);
        $comparator = $match[1] ?: '=';
        $normalized = self::normalizeDecimal($match[2]);

        return $query->where($field, null === $normalized ? 0.0 : (float) $normalized, $comparator);
    }

    public static function getListValue($params)
    {
        return Integer::getListValue($params);
    }
}
