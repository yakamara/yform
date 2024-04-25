<?php
/**
 * Helper class for yform/manager plugin history context.
 *
 * @category helper
 * @author Peter Schulze | p.schulze[at]bitshifters.de
 * @created 17.04.2024
 * @package redaxo\yform\manager
 */
class rex_yform_history_helper
{
    /** @var array<string> field type icons */
    private const FIELD_TYPE_ICONS = [
        'question' => 'question',

        'checkbox' => 'square-check',
        'select' => 'list-check',
        'choice' => 'list-check',
        'choice_radio' => 'circle-dot',
        'choice_checkbox' => 'square-check',
        'date' => 'calendar',
        'datetime' => 'calendar-day',
        'datestamp' => 'calendar-day',
        'be_link' => 'link',
        'custom_link' => 'link',
        'be_media' => 'photo-film',
        'be_manager_relation' => 'database',
        'be_table' => 'table',
        'be_user' => 'user',
        'integer' => '1',
        'number' => '1',
        'ip' => 'network-wired',
        'generate_key' => 'key',
        'php' => 'code',
        'prio' => 'arrow-up-1-9',
        'signature' => 'signature',
        'submit' => 'fire',
        'time' => 'clock',
        'upload' => 'upload',
        'uuid' => 'key',
        'email' => 'at',
    ];

    /** @var string field type icons font width class */
    private const FIELD_TYPE_ICON_WEIGHT_CLASS = 'far';

    /**
     * detect diffs in 2 strings.
     * @return array<string|array<string>>
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     * @created 17.04.2024
     * @copyright https://github.com/paulgb/simplediff | Paul Butler (paulgb)
     * @api
     */
    public static function diffStrings($old, $new): array
    {
        $matrix = [];
        $maxlen = $omax = $nmax = 0;

        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue, true);

            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] =
                    isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 :
                    1
                ;

                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }

        if (0 === $maxlen) {
            return [['d' => $old, 'i' => $new]];
        }

        return array_merge(
            self::diffStrings(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            self::diffStrings(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)),
        );
    }

    /**
     * detect diffs in 2 strings and return as html.
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     * @created 17.04.2024
     * @copyright https://github.com/paulgb/simplediff | Paul Butler (paulgb)
     * @api
     */
    public static function diffStringsToHtml($old, $new): string
    {
        $ret = '';
        $diff = self::diffStrings(preg_split('/[\\s]+/', $old), preg_split('/[\\s]+/', $new));

        foreach ($diff as $k) {
            if (is_array($k)) {
                $ret .=
                    (isset($k['d']) && is_array($k['d']) && count($k['d']) > 0 ? '<del>' . implode(' ', $k['d']) . '</del> ' : '') .
                    (isset($k['i']) && is_array($k['i']) && count($k['i']) > 0 ? '<ins>' . implode(' ', $k['i']) . '</ins> ' : '')
                ;
            } else {
                $ret .= $k . ' ';
            }
        }

        return trim($ret);
    }

    /**
     * get icon for yform field type.
     * @param bool $outputHtml print <i></i>
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     * @created 22.04.2024
     */
    public static function getFieldTypeIcon(rex_yform_manager_field $field, bool $addPrefix = true, bool $outputHtml = true, bool $addTooltip = true, string $tooltipPlacement = 'top'): string
    {
        $icon = self::FIELD_TYPE_ICONS[$field->getTypeName()] ?? 'default';
        $tag = isset(self::FIELD_TYPE_ICONS[$field->getTypeName()]) ? 'i' : 'span';

        switch ($field->getTypeName()) {
            case 'choice':
                $expanded = (bool) (int) $field->getElement('expanded');
                $multiple = (bool) (int) $field->getElement('multiple');

                if ($expanded && $multiple) {
                    $icon = self::FIELD_TYPE_ICONS['choice_checkbox'];
                } elseif ($expanded) {
                    $icon = self::FIELD_TYPE_ICONS['choice_radio'];
                }
                break;
        }

        return ($outputHtml ? '<' .
                    $tag .
                    ($addTooltip ? ' data-toggle="tooltip" data-placement="' . $tooltipPlacement . '" title="' . rex_i18n::msg('yform_manager_type_name') . ': ' . $field->getTypeName() . '"' : '') .
                    ' class="' : ''
        ) .
               ('default' !== $icon ? self::FIELD_TYPE_ICON_WEIGHT_CLASS . ' ' : '') . ($addPrefix ? 'rex-icon ' : '') . 'fa-' . $icon .
               ($outputHtml ? '"></' . $tag . '>' : '');
    }

    /**
     * get field value.
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     * @created 22.04.2024
     */
    public static function getFieldValue(rex_yform_manager_field $field, rex_yform_manager_dataset $dataset, rex_yform_manager_table $table): string
    {
        $class = 'rex_yform_value_' . $field->getTypeName();
        $currentValue = ($dataset->hasValue($field->getName()) ? $dataset->getValue($field->getName()) : '-');

        if (
            is_callable([$class, 'getListValue']) &&
            !in_array($field->getTypeName(), ['text', 'textarea'], true)
        ) {
            // get (formatted) value for current entry
            if ($dataset->hasValue($field->getName())) {
                $currentValue = $class::getListValue([
                    'value' => $currentValue,
                    'subject' => $currentValue,
                    'field' => $field->getName(),
                    'params' => [
                        'field' => $field->toArray(),
                        'fields' => $table->getFields(),
                    ],
                ]);
            } else {
                $currentValue = '-';
            }
        } else {
            $currentValue = rex_escape($currentValue);
        }

        return $currentValue;
    }
}
