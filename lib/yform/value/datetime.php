<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_datetime extends rex_yform_value_abstract
{
    public const VALUE_DATETIME_DEFAULT_FORMAT = 'Y-m-d H:i:s';
    public const VALUE_DATETIME_FORMATS = ['d.m.Y H:i:s' => 'd.m.Y H:i:s', 'Y-m-d H:i:s' => 'Y-m-d H:i:s', 'd-m-Y H:i:s' => 'd-m-Y H:i:s', 'm-d-Y H:i:s' => 'm-d-Y H:i:s', 'm-Y H:i:s' => 'm-Y H:i:s', 'Y-m H:i:s' => 'Y-m H:i:s', 'd-m H:i:s' => 'd-m H:i:s', 'm-d H:i:s' => 'm-d H:i:s', 'Y' => 'Y', 'Y-m' => 'Y-m'];

    public function preValidateAction(): void
    {
        $value = $this->getValue();
        if (1 == $this->getElement('current_date') && '' == $this->getValue() && $this->params['main_id'] < 1) {
            $value = date('Y-m-d H:i:s');
        }
        if (is_array($value)) {
            $year = (int) ($value['year'] ?? 0);
            $month = (int) ($value['month'] ?? 0);
            $day = (int) ($value['day'] ?? 0);
            $hour = (int) ($value['hour'] ?? 0);
            $minute = (int) ($value['minute'] ?? 0);
            $second = (int) ($value['second'] ?? 0);
        } else {
            $value = (string) $value;
            $value = explode(' ', $value);
            if (2 == count($value)) {
                $date = explode('-', (string) $value[0]);
                $year = (int) ($date[0] ?? 0);
                $month = (int) ($date[1] ?? 0);
                $day = (int) ($date[2] ?? 0);
                $hms = explode(':', (string) $value[1]);
                $hour = (int) ($hms[0] ?? 0);
                $minute = (int) ($hms[1] ?? 0);
                $second = (int) ($hms[2] ?? 0);
            }
        }
        $value = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year ?? 0, $month ?? 0, $day ?? 0, $hour ?? 0, $minute ?? 0, $second ?? 0);
        $this->setValue($value);
    }

    public function enterObject()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            $year = (int) ($value['year'] ?? 0);
            $month = (int) ($value['month'] ?? 0);
            $day = (int) ($value['day'] ?? 0);
            $hour = (int) ($value['hour'] ?? 0);
            $minute = (int) ($value['minute'] ?? 0);
            $second = (int) ($value['second'] ?? 0);
        } else {
            $value = (string) $value;
            $value = explode(' ', $value);
            if (2 == count($value)) {
                $date = explode('-', (string) $value[0]);
                $year = (int) ($date[0] ?? 0);
                $month = (int) ($date[1] ?? 0);
                $day = (int) ($date[2] ?? 0);
                $hms = explode(':', (string) $value[1]);
                $hour = (int) ($hms[0] ?? 0);
                $minute = (int) ($hms[1] ?? 0);
                $second = (int) ($hms[2] ?? 0);
            }
        }
        $value = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year ?? 0, $month ?? 0, $day ?? 0, $hour ?? 0, $minute ?? 0, $second ?? 0);
        $this->setValue($value);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput() && !$this->isViewable()) {
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
        } elseif ('' != $this->getElement('year_end')) {
            $yearEnd = (int) $this->getElement('year_end');
        } else {
            $yearEnd = date('Y');
        }

        if ($yearEnd < $yearStart) {
            $yearEnd = $yearStart + 20;
        }

        if (!$this->isEditable()) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.datetime-view.tpl.php', 'value.date-view.tpl.php', 'value.view.tpl.php'],
                ['type' => 'text', 'value' => self::datetime_getFormattedDatetime($this->getElement('format'), $this->getValue())]
            );
        } elseif ('input:text' == $this->getElement('widget')) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.text.tpl.php'],
                ['type' => 'text', 'value' => $this->getValue()]
            );
        } else {
            $format = 'YYYY-MM-DD HH:ii:ss'; // Format of Select Order
            $year = (int) mb_substr($this->getValue(), 0, 4);
            $month = (int) mb_substr($this->getValue(), 5, 2);
            $day = (int) mb_substr($this->getValue(), 8, 2);
            $hour = (int) mb_substr($this->getValue(), 11, 2);
            $minute = (int) mb_substr($this->getValue(), 14, 2);
            $second = (int) mb_substr($this->getValue(), 17, 2);
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.date.tpl.php', 'value.datetime.tpl.php'],
                compact('format', 'yearStart', 'yearEnd', 'year', 'month', 'day', 'hour', 'minute', 'second')
            );
        }
    }

    public static function datetime_getFormattedDatetime($format, $date)
    {
        $format = (in_array($format, self::VALUE_DATETIME_FORMATS, true)) ? $format : self::VALUE_DATETIME_DEFAULT_FORMAT;
        $DTdate = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return (!$date || !$DTdate || $date != $DTdate->format('Y-m-d H:i:s')) ? '['.$date.']' : $DTdate->format($format);
    }

    public function getDescription(): string
    {
        return 'datetime|name|label| jahrstart | jahrsende | [Anzeigeformat YYYY-MM-DD HH:ii:ss] |[1/Aktuelles Datum voreingestellt]|[no_db]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'datetime',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'year_start' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_datetime_year_start')],
                'year_end' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_datetime_year_end')],
                'format' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_datetime_format'), 'choices' => self::VALUE_DATETIME_FORMATS, 'default' => self::VALUE_DATETIME_DEFAULT_FORMAT],
                'current_date' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_datetime_current_date')],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'widget' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_defaults_widgets'), 'choices' => ['select' => 'select', 'input:text' => 'input:text'], 'default' => 'select'],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => 'Datum & Uhrzeit Eingabe',
            'db_type' => ['datetime'],
        ];
    }

    public static function getListValue($params): string
    {
        return '<nobr>'.self::datetime_getFormattedDatetime($params['params']['field']['format'], $params['subject']).'</nobr>';
    }

    public static function getSearchField($params)
    {
        $format = 'YYYY-MM-DD HH:ii:ss';
        $params['searchForm']->setValueField('text', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'notice' => rex_i18n::msg('yform_values_date_search_notice', $format),
            'attributes' => '{"data-yform-tools-datetimerangepicker":"' . $format . '"}', ]);
    }

    public static function getSearchFilter($params)
    {
        $value = trim($params['value']);
        /** @var rex_yform_manager_query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();

        if ('' == $value) {
            return $query;
        }

        $format = 'YYYY-MM-DD HH:ii:ss';
        $format_len = mb_strlen($format);
        $firstchar = mb_substr($value, 0, 1);

        switch ($firstchar) {
            case '>':
            case '<':
            case '=':
                $value = mb_substr($value, 1);
                return $query->where($field, $value, $firstchar);
        }

        if (mb_strlen($value) == $format_len) {
            return $query->where($field, $value);
        }

        $dates = explode(' - ', $value);
        if (2 == count($dates)) {
            $date_from = $dates[0];
            $date_to = $dates[1];

            return $query
                    ->where($field, $date_from, '>=')
                    ->where($field, $date_to, '<=');
        }

        // plain compare
        return $query->where($field, $value);
    }
}
