<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_date extends rex_yform_value_abstract
{
    public const VALUE_DATE_DEFAULT_FORMAT = 'YYYY-MM-DD';
    public const VALUE_DATE_FORMATS = ['DD.MM.YYYY' => 'DD.MM.YYYY', 'YYYY-MM-DD' => 'YYYY-MM-DD', 'DD-MM-YYYY' => 'DD-MM-YYYY', 'MM-DD-YYYY' => 'MM-DD-YYYY', 'YYYY' => 'YYYY', 'MM' => 'MM', 'MM-YYYY' => 'MM-YYYY', 'YYYY-MM' => 'YYYY-MM'];

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

    public function preValidateAction()
    {
        // if date is unformated
        $value = $this->getValue();
        if (is_string($value) && '' != $value) {
            if (8 == strlen($value)) {
                if ($d = DateTime::createFromFormat('Ymd', $value)) {
                    if ($d->format('Ymd') == $value) {
                        $this->setValue($d->format('Y-m-d'));
                        return;
                    }
                }
            } else {
                if ($d = date_create_from_format('Y-m-d', $value)) {
                    if ($d->format('Y-m-d') == $value) {
                        return;
                    }
                }
            }
        }

        if (1 == $this->getElement('current_date') && '' == $this->getValue() && $this->params['main_id'] < 1) {
            $this->setValue(date('Y-m-d'));
        }

        if ($this->params['send']) {
            $value = $this->getValue();

            if (is_array($value)) {
                // widget: choice
                $year = (int) substr(@$value['year'], 0, 4);
                $month = (int) substr(@$value['month'], 0, 2);
                $day = (int) substr(@$value['day'], 0, 2);

                $value =
                    str_pad($year, 4, '0', STR_PAD_LEFT) . '-' .
                    str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
                    str_pad($day, 2, '0', STR_PAD_LEFT);
            } else {
                // widget: input:text
                $format = self::date_getFormat($this->getElement('format'));
                $value = (string) self::date_getFromFormattedDate($this->getValue(), $format, 'YYYY-MM-DD');
            }

            $this->setValue($value);
        }
    }

    public function enterObject()
    {
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput()) {
            return;
        }

        if ('-' == substr($this->getElement('year_start'), 0, 1)) {
            $minus_years = (int) substr($this->getElement('year_start'), 1);
            $yearStart = date('Y') - $minus_years;
        } elseif ('' == $this->getElement('year_start')) {
            $yearStart = date('Y') - 20;
        } else {
            $yearStart = (int) $this->getElement('year_start');
        }

        if ('+' == substr($this->getElement('year_end'), 0, 1)) {
            $add_years = (int) substr($this->getElement('year_end'), 1);
            $yearEnd = date('Y') + $add_years;
        } else {
            $yearEnd = (int) $this->getElement('year_end');
        }

        if ($yearEnd <= $yearStart) {
            $yearEnd = date('Y') + 10;
        }

        $year = (int) substr($this->getValue(), 0, 4);
        $month = (int) substr($this->getValue(), 5, 2);
        $day = (int) substr($this->getValue(), 8, 2);

        $format = self::date_getFormat($this->getElement('format'));
        $input_value = self::date_getFromFormattedDate($this->getValue(), 'YYYY-MM-DD', $format);

        if ('input:text' == $this->getElement('widget')) {
            if ('00000000' == self::date_getFromFormattedDate($this->getValue(), $format, 'YYYYMMDD')) {
                $input_value = '';
            }
            $this->params['form_output'][$this->getId()] = $this->parse(['value.text.tpl.php'], ['type' => 'text', 'value' => $input_value]);
        } else {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.date.tpl.php', 'value.datetime.tpl.php'],
                compact('format', 'yearStart', 'yearEnd', 'year', 'month', 'day')
            );
        }
    }

    public static function date_getFormat($format)
    {
        return (in_array($format, self::VALUE_DATE_FORMATS, true)) ? $format : self::VALUE_DATE_DEFAULT_FORMAT;
    }

    public static function date_getFromFormattedDate($datestring, $format, $returnDateFormat = 'YYYY-MM-DD')
    {
        $year = 0;
        $pos = strpos($format, 'YYYY');
        if (false !== $pos) {
            $year = (int) substr($datestring, $pos, 4);
        }
        $year = str_pad($year, 4, '0', STR_PAD_LEFT);

        $month = 0;
        $pos = strpos($format, 'MM');
        if (false !== $pos) {
            $month = (int) substr($datestring, $pos, 2);
        }
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);

        $day = 0;
        $pos = strpos($format, 'DD');
        if (false !== $pos) {
            $day = (int) substr($datestring, $pos, 2);
        }
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);

        return str_replace(['YYYY', 'MM', 'DD'], [$year, $month, $day], $returnDateFormat);
    }

    public function getDescription()
    {
        return 'date|name|label| [jahrstart/-5] | [jahrende/+5 ]| [Anzeigeformat YYYY-MM-DD] | [1/Aktuelles Datum voreingestellt] | [no_db] ';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'date',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'year_start' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_date_year_start')],
                'year_end' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_date_year_end')],
                'format' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_date_format'), 'choices' => self::VALUE_DATE_FORMATS, 'default' => self::VALUE_DATE_DEFAULT_FORMAT],
                'current_date' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_date_current_date')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table')],
                'widget' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_defaults_widgets'), 'choices' => ['select' => 'select', 'input:text' => 'input:text'], 'default' => 'select'],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_date_description'),
            'db_type' => ['date'],
            'famous' => true,
        ];
    }

    public static function getListValue($params)
    {
        $format = self::date_getFormat(($params['params']['field']['format']) ?? self::VALUE_DATE_DEFAULT_FORMAT);
        return '<nobr>'.self::date_getFromFormattedDate($params['subject'], self::VALUE_DATE_DEFAULT_FORMAT, $format).'</nobr>';
    }

    public static function getSearchField($params)
    {
        // 01/15/2015 - 02/15/2015
        $format = self::date_getFormat($params['field']->getElement('format'));
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel(), 'notice' => rex_i18n::msg('yform_values_date_search_notice', $format), 'attributes' => '{"data-yform-tools-daterangepicker":"' . $format . '"}']);
    }

    public static function getSearchFilter($params)
    {
        $value = trim($params['value']);
        $format = self::date_getFormat($params['field']->getElement('format'));
        $field = $params['field']->getName();
        $field = 't0.' . rex_sql::factory()->escapeIdentifier($field);
        return self::getDateFilterWhere($value, $field, $format);
    }

    /**
     * allow external call in not-searchform-context.
     *
     * @param string $value  search criteria
     * @param string $field  db-field
     * @param string $format date-format as defined in self::VALUE_DATE_FORMATS
     *
     * @return string WHERE-clause
     */
    public static function getDateFilterWhere($value, $field, $format)
    {
        // kein Suchtext => kein Filter
        if ('' == $value) {
            return '';
        }

        // Auswertung über Pattern: <|<=|=|>=|> $value
        $pattern = '/^(?<c>\<=|\<|=|\>=|\>)?\s*' . self::VALUE_SEARCH_PATTERN[$format] . '$/';
        $ok = preg_match($pattern, $value, $match);
        if ($ok) {
            $comparator = $match['c'] ?: '=';
            $year = $match['y'] ?: null;
            if (2 == strlen($year)) {
                $year = '20' . $year;
            }
            $month = $match['m'] ?? null;
            $day = $match['d'] ?? null;

            if (null != $year) {
                if (null != $month) {
                    // Abfrage auf ein konkretes Datum YYYY-MM-DD, etc.
                    if (null != $day) {
                        return '( ' . self::createDbDateComparison($field, $comparator, $year, $month, $day) . ' )';
                    }

                    // Abfrage auf YYYY-MM (=)
                    // =2020-02  -->  2020-02-00 <= db <= 2020-02-99
                    if ('=' == $comparator) {
                        $from = self::createDbDateComparison($field, '>=', $year, $month, '00');
                        $to = self::createDbDateComparison($field, '<=', $year, $month, '99');
                        return "( $from AND $to )";
                    }

                    // Abfrage auf YYYY-MM (alle übrigen)
                    // <2020-02   -->  < 2020-02-00
                    // <=2020-02  -->  < 2020-02-99
                    // >=2020-02  -->  > 2020-02-00
                    // >2020-02   -->  > 2020-02-99
                    $day = ('<' == $comparator || '>=' == $comparator) ? '00' : '99';
                    return '( ' . self::createDbDateComparison($field, $comparator, $year, $month) . ' )';
                }

                // Abfrage auf YYYY
                return "( YEAR($field) $comparator $year )";
            }

            if (null != $month) {
                return "( MONTH($field) $comparator $month )";
            }
        }

        // Range-Auswertung über Pattern: $value1 - $value2
        $pattern2 = str_replace(['<y>', '<m>', '<d>'], ['<y2>', '<m2>', '<d2>'], self::VALUE_SEARCH_PATTERN[$format]);
        $pattern = '/^'.self::VALUE_SEARCH_PATTERN[$format].'\s* - \s*' . $pattern2 . '$/';
        $ok = preg_match($pattern, $value, $match);
        if ($ok) {
            $year_from = $match['y'] ?: '';
            if (2 == strlen($year_from)) {
                $year_from = '20' . $year_from;
            }
            $year_to = $match['y2'] ?: '';
            if (2 == strlen($year_to)) {
                $year_to = '20' . $year_to;
            }
            $month_from = $match['m'] ?: '00';
            $month_to = $match['m2'] ?: '99';
            $day_from = $match['d'] ?: '00';
            $day_to = $match['d2'] ?: '99';

            if ('YYYY' == $format) {
                return "( YEAR($field) >= $year_from AND YEAR($field) <= $year_to )";
            }

            if ('MM' == $format) {
                return "( MONTH($field) >= $month_from AND MONTH($field) <= $month_to )";
            }

            $from = self::createDbDateComparison($field, '>=', $year_from, $month_from, $day_from);
            $to = self::createDbDateComparison($field, '<=', $year_to, $month_to, $day_to);
            return "( $from AND $to )";
        }

        // ungültige bzw. nicht verwertbare Eingabe ( kein valides SQL möglich )
        // -> interpretiert als: "kein Satz entspricht dem Kriterium"
        return '( false )';
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
    public static function createDbDateComparison($field, $comparator, $year, $month = null, $day = null)
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
