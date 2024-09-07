<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_date extends rex_yform_value_abstract
{
    public const VALUE_DATE_DEFAULT_FORMAT = 'Y-m-d';
    public const VALUE_DATE_FORMATS = ['d.m.Y' => 'd.m.Y', 'Y-m-d' => 'Y-m-d', 'd-m-Y' => 'd-m-Y', 'm-d-Y' => 'm-d-Y', 'Y' => 'Y', 'm' => 'm', 'm-Y' => 'm-Y', 'Y-m' => 'Y-m'];

    // Um im Suchformular weitergehende Auswertungen zu machen
    public const VALUE_SEARCH_PATTERN = [
        'DD.MM.YYYY' => '(?:(?<d>\d{1,2}(?=\.\d{1,2}\.))\.)?(?:(?<m>\d{1,2})\.)?(?<y>(?:\d{2}|\d{4}))',
        'YYYY-MM-DD' => '(?<y>(?:\d{2}|\d{4}))(?:-(?<m>\d{1,2}))?(?:-(?<d>\d{1,2}))?',
        'DD-MM-YYYY' => '(?:(?<d>\d{1,2}(?=-\d{1,2}-))-)?(?:(?<m>\d{1,2})-)?(?<y>(?:\d{2}|\d{4}))',
        'MM-DD-YYYY' => '(?:(?<m>\d{1,2})-)?(?:(?<d>\d{1,2})-)?(?<y>(?:\d{2}|\d{4}))',
        'YYYY' => '(?<y>(?:\d{2}|\d{4}))',
        'MM' => '(?<m>\d{1,2})',
        'MM-YYYY' => '(?:(?<m>\d{1,2})-)?(?<y>(?:\d{2}|\d{4}))',
        'YYYY-MM' => '(?<y>(?:\d{2}|\d{4}))(?:-(?<m>\d{1,2}))?',
    ];

    public function preValidateAction(): void
    {
        $value = $this->getValue();
        if ('' == $this->getValue() && $this->params['main_id'] < 1) {
            if (1 == $this->getElement('current_date')) {
                $value = date('Y-m-d');
            }
            if ('' != $this->getElement('modify_default')) {
                $dt = new DateTime();
                if (false !== @$dt->modify($this->getElement('modify_default'))) {
                    $value = $dt->format('Y-m-d');
                }
            }
        }

        if (is_array($value)) {
            $year = (int) ($value['year'] ?? 0);
            $month = (int) ($value['month'] ?? 0);
            $day = (int) ($value['day'] ?? 0);
        } else {
            $value = explode('-', (string) $value);
            $year = (int) ($value[0] ?? 0);
            $month = (int) ($value[1] ?? 0);
            $day = (int) ($value[2] ?? 0);
        }
        $value = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $this->setValue($value);
    }

    public function enterObject()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            $year = (int) ($value['year'] ?? 0);
            $month = (int) ($value['month'] ?? 0);
            $day = (int) ($value['day'] ?? 0);
        } else {
            $value = explode('-', (string) $value);
            $year = (int) ($value[0] ?? 0);
            $month = (int) ($value[1] ?? 0);
            $day = (int) ($value[2] ?? 0);
        }
        $value = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $this->setValue($value);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue() ?? '';
        }

        if (!$this->needsOutput() || !$this->isViewable()) {
            return;
        }

        if ('-' == mb_substr($this->getElement('year_start'), 0, 1)) {
            $minus_years = (int) mb_substr($this->getElement('year_start'), 1);
            $yearStart = date('Y') - $minus_years;
        } elseif ('' == $this->getElement('year_start')) {
            $yearStart = date('Y') - 20;
        } else {
            $yearStart = (int) $this->getElement('year_start');
        }

        if ('+' == mb_substr($this->getElement('year_end'), 0, 1)) {
            $add_years = (int) mb_substr($this->getElement('year_end'), 1);
            $yearEnd = date('Y') + $add_years;
        } else {
            $yearEnd = (int) $this->getElement('year_end');
        }

        if ($yearEnd < $yearStart) {
            $yearEnd = date('Y') + 10;
        }

        if (!$this->isEditable()) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.date-view.tpl.php', 'value.datetime-view.tpl.php', 'value.view.tpl.php'],
                ['type' => 'text', 'value' => self::date_getFormattedDate($this->getElement('format'), $this->getValue())],
            );
        } elseif ('input:text' == $this->getElement('widget')) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.text.tpl.php'],
                ['type' => 'text', 'value' => $this->getValue()],
            );
        } elseif ('input:date' == $this->getElement('widget')) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.text.tpl.php'],
                ['type' => 'date', 'value' => $this->getValue()],
            );
        } else {
            $format = 'YYYY-MM-DD'; // Format of Select Order
            $year = (int) mb_substr($this->getValue(), 0, 4);
            $month = (int) mb_substr($this->getValue(), 5, 2);
            $day = (int) mb_substr($this->getValue(), 8, 2);
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.date.tpl.php', 'value.datetime.tpl.php'],
                compact('format', 'yearStart', 'yearEnd', 'year', 'month', 'day'),
            );
        }
    }

    public static function date_getFormattedDate($format, $date)
    {
        $format = (in_array($format, self::VALUE_DATE_FORMATS, true)) ? $format : self::VALUE_DATE_DEFAULT_FORMAT;
        $DTdate = DateTime::createFromFormat('Y-m-d', $date);
        return (!$DTdate || $date != $DTdate->format('Y-m-d')) ? '[' . $date . ']' : $DTdate->format($format);
    }

    public function getDescription(): string
    {
        return 'date|name|label| [jahrstart/-5] | [jahrende/+5 ]| [Anzeigeformat YYYY-MM-DD] | [1/Aktuelles Datum voreingestellt] | [no_db] ';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'date',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'year_start' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_date_year_start')],
                'year_end' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_date_year_end')],
                'format' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_date_format'), 'choices' => self::VALUE_DATE_FORMATS, 'default' => self::VALUE_DATE_DEFAULT_FORMAT, 'notice' => rex_i18n::msg('yform_values_format_show_notice')],
                'current_date' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_date_current_date')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table')],
                'widget' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_defaults_widgets'), 'choices' => ['select' => 'select', 'input:text' => 'input:text', 'input:date' => 'input:date'], 'default' => 'select'],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'modify_default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_date_modify_default'), 'notice' => rex_i18n::msg('yform_values_date_modify_default_notics')],
            ],
            'description' => rex_i18n::msg('yform_values_date_description'),
            'db_type' => ['date'],
            'famous' => true,
        ];
    }

    public static function getListValue($params)
    {
        return '<nobr>' . self::date_getFormattedDate($params['params']['field']['format'], $params['subject']) . '</nobr>';
    }

    public static function getSearchField($params)
    {
        $format = 'YYYY-MM-DD';
        $params['searchForm']->setValueField('text', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'notice' => rex_i18n::msg('yform_values_date_search_notice', $format),
            'attributes' => '{"data-yform-tools-daterangepicker":"' . $format . '"}',
        ]);
    }

    public static function getSearchFilter($params)
    {
        $value = trim($params['value']);
        /** @var \Yakamara\YForm\Manager\Query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();

        $format = 'YYYY-MM-DD';

        if ('' == $value) {
            return $query;
        }

        // Auswertung über Pattern: <|<=|=|>=|> $value
        $pattern = '/^(?<c>\<=|\<|=|\>=|\>)?\s*' . self::VALUE_SEARCH_PATTERN[$format] . '$/';
        $ok = preg_match($pattern, $value, $match);
        if ($ok) {
            $comparator = $match['c'] ?: '=';
            $year = $match['y'] ?: null;
            if (2 == mb_strlen($year)) {
                $year = '20' . $year;
            }
            $month = $match['m'] ?? null;
            $day = $match['d'] ?? null;

            if (null != $year) {
                if (null != $month) {
                    // Abfrage auf ein konkretes Datum YYYY-MM-DD, etc.
                    if (null != $day) {
                        return $query->whereRaw('(' . self::date_createDbDateComparison($field, $comparator, $year, $month, $day) . ')');
                    }

                    // Abfrage auf YYYY-MM (=)
                    // =2020-02  -->  2020-02-00 <= db <= 2020-02-99
                    if ('=' == $comparator) {
                        $from = self::date_createDbDateComparison($field, '>=', $year, $month, '00');
                        $to = self::date_createDbDateComparison($field, '<=', $year, $month, '99');
                        return $query->whereRaw('(' . $from . ' AND ' . $to . ')');
                    }

                    // Abfrage auf YYYY-MM (alle übrigen)
                    // <2020-02   -->  < 2020-02-00
                    // <=2020-02  -->  < 2020-02-99
                    // >=2020-02  -->  > 2020-02-00
                    // >2020-02   -->  > 2020-02-99
                    $day = ('<' == $comparator || '>=' == $comparator) ? '00' : '99';
                    return $query->whereRaw('(' . self::date_createDbDateComparison($field, $comparator, $year, $month) . ')');
                }

                // Abfrage auf YYYY
                return $query->whereRaw('( YEAR(' . $field . ') ' . $comparator . ' ' . $year . ' )');
            }

            if (null != $month) {
                return $query->whereRaw('( MONTH(' . $field . ') ' . $comparator . ' ' . $month . ' )');
            }
        }

        // Range-Auswertung über Pattern: $value1 - $value2
        $pattern2 = str_replace(['<y>', '<m>', '<d>'], ['<y2>', '<m2>', '<d2>'], self::VALUE_SEARCH_PATTERN[$format]);
        $pattern = '/^' . self::VALUE_SEARCH_PATTERN[$format] . '\s* - \s*' . $pattern2 . '$/';
        $ok = preg_match($pattern, $value, $match);
        if ($ok) {
            $year_from = $match['y'] ?: '';
            if (2 == mb_strlen($year_from)) {
                $year_from = substr(date('Y'), 0, 2) . $year_from;
            }
            $year_to = $match['y2'] ?: '';
            if (2 == mb_strlen($year_to)) {
                $year_to = substr(date('Y'), 0, 2) . $year_to;
            }

            $month_from = $match['m'] ?? '00';
            $month_to = $match['m2'] ?? '99';
            $day_from = $match['d'] ?? '00';
            $day_to = $match['d2'] ?? '99';

            if (4 == mb_strlen($value)) {
                return $query->whereRaw('( YEAR(' . $field . ') >= ' . $year_from . ' AND YEAR(' . $field . ') <= ' . $year_to . ' )');
            }

            $from = self::date_createDbDateComparison($field, '>=', $year_from, $month_from, $day_from);
            $to = self::date_createDbDateComparison($field, '<=', $year_to, $month_to, $day_to);
            return $query->whereRaw('( ' . $from . ' AND ' . $to . ' )');
        }

        // ungültige bzw. nicht verwertbare Eingabe ( kein valides SQL möglich )
        // -> interpretiert als: "kein Satz entspricht dem Kriterium"
        return $query->whereRaw('( false )');
    }

    /**
     * @params string $field        Feldame in Ticks (z.B. `datum`)
     * @params string $comparator   < | <= | = | >= | >
     * @params string $year         immer 4 Stellen, durch regex bereits sichergestellt
     * @params null|string $month   0-2 Stellen, muss aufgefüllt werden
     * @params null|string $day     0-2 Stellen, muss aufgefüllt werden
     *
     * @return string SQL-Such-Term
     */
    public static function date_createDbDateComparison($field, $comparator, $year, $month = null, $day = null)
    {
        $len = 0;
        $value = '';
        if (null != $month) {
            $value = str_pad($month, 2, '0', STR_PAD_LEFT);
            $len += 3;
            if (null != $day) {
                $value .= '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                $len += 3;
            }
        }
        if ($len) {
            $len += 4;
            if (10 == $len) {
                return "CAST($field AS CHAR) $comparator '$year-$value'";
            }
            return "SUBSTR($field,1,$len) $comparator '$year-$value'";
        }
        return "YEAR($field) $comparator $year";
    }
}
