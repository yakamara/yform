<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_list extends rex_list
{
    private $columnLabels = [];
    private $columnFormates = [];

    public static function factory($query, $rowsPerPage = 30, $listName = null, $debug = false, $class = null)
    {
        if (!$class) {
            $class = rex_extension::registerPoint(new rex_extension_point('REX_LIST_CLASSNAME', 'rex_yform_list',
                    [
                        'query' => $query,
                        'rowsPerPage' => $rowsPerPage,
                        'listName' => $listName,
                        'debug' => $debug,
                    ]
                )
            );
        }

        return new $class($query, $rowsPerPage, $listName, $debug);
    }

    public function getPagination()
    {
        $s = $this->getPlainView();
        // $s .= $this->getClassicView();

        return $s;
    }

    // ------------------------------------------------------- VIEWS

    public function getClassicView()
    {
        $start = $this->getStartRow();
        $rows = $this->getRows();
        $rowsPerPage = $this->getRowsPerPage();
        $pages = ceil($rows / $rowsPerPage);

        $s = '<ul class="rex-navi-paginate">' . "\n";
        $s .= '<li class="rex-navi-paginate-prev"><a href="' . $this->getUrl(['start' => $start - $rowsPerPage]) . '" title="' . rex_i18n::msg('list_previous') . '"><span>' . rex_i18n::msg('list_previous') . '</span></a></li>';

        if ($pages > 1) {
            for ($i = 1; $i <= $pages; ++$i) {
                $first = ($i - 1) * $rowsPerPage;
                $last = $i * $rowsPerPage;

                if ($last > $rows) {
                    $last = $rows;
                }

                $pageLink = $i;
                if ($start != $first) {
                    $pageLink = '<a href="' . $this->getUrl(['start' => $first]) . '"><span>' . $pageLink . '</span></a>';
                } else {
                    $pageLink = '<a class="rex-active" href="' . $this->getUrl(['start' => $first]) . '"><span>' . $pageLink . '</span></a>';
                }

                $s .= '<li class="rex-navi-paginate-page">' . $pageLink . '</li>';
            }
        }
        $s .= '<li class="rex-navi-paginate-next"><a href="' . $this->getUrl(['start' => $start + $rowsPerPage]) . '" title="' . rex_i18n::msg('list_next') . '"><span>' . rex_i18n::msg('list_next') . '</span></a></li>';
        $s .= '<li class="rex-navi-paginate-message"><span>' . rex_i18n::msg('list_rows_found', $this->getRows()) . '</span></li>';
        $s .= '</ul>' . "\n";

        return '<div class="rex-navi-paginate rex-toolbar"><div class="rex-toolbar-content">' . $s . '<div class="rex-clearer"></div></div></div>';
    }

    public function getPlainView()
    {
        $current = $this->getStartRow();

        if ($current > $this->getRows() || $current < 0) {
            $current = 0;
        }

        $last = ((int) ($this->getRows() / $this->getRowsPerPage()) * $this->getRowsPerPage()) - $this->getRowsPerPage();
        $next = $current + $this->getRowsPerPage();
        if ($next >= $this->getRows()) {
            $next = '';
        }
        $prev = $current - $this->getRowsPerPage();
        if ($prev < 0) {
            $prev = '';
        }

        $page_current = (int) ($current / $this->getRowsPerPage());
        $page_all = (int) (($this->getRows() - 1) / $this->getRowsPerPage());
        $entries = $current + $this->getRowsPerPage();
        if ($entries > $this->getRows()) {
            $entries = ($page_current * $this->getRowsPerPage()) + $this->getRows() - ($page_current * $this->getRowsPerPage());
        }

        $return = '';
        if ($prev !== '') {
            $return .= '<li class="rex-navi-paginate-prev"><a class="rex-active" href="' . $this->getUrl(['start' => $prev]) . '" title="'.rex_i18n::msg('list_previous').'"><span>' . rex_i18n::msg('list_previous') . '</span></a></li>';
        } else {
            $return .= '<li class="rex-navi-paginate-prev"><a href="javascript:void(0);"><span>' . rex_i18n::msg('list_previous') . '</span></a></li>';
        }

        $show_pages = [];
        $show_pages[0] = 0;
        $show_pages[1] = 1;
        $show_pages[2] = 2;
        $show_pages[$page_all - 2] = $page_all - 2;
        $show_pages[$page_all - 1] = $page_all - 1;
        $show_pages[$page_all] = $page_all;
        if ($page_all > 6) {
            if ($page_current < ($page_all / 3) || $page_current > ($page_all / 3 * 2)) {
                $m = (int) ($page_all / 2);
                $show_pages[$m - 1] = $m - 1;
                $show_pages[$m] = $m;
                $show_pages[$m + 1] = $m + 1;
            }

            $show_pages[$page_current - 1] = $page_current - 1;
            $show_pages[$page_current] = $page_current;
            $show_pages[$page_current + 1] = $page_current + 1;
        }

        $dot = true;
        for ($i = 0; $i <= $page_all; ++$i) {
            if ($page_current == $i) {
                $return .= '<li class="rex-navi-paginate-page"><a class="rex-active" href="' . $this->getUrl(['start' => ($i * $this->getRowsPerPage())]) . '"><span>' . ($i + 1) . '</span></a></li>';
                $dot = true;
            } elseif (in_array($i, $show_pages)) {
                $return .= '<li class="rex-navi-paginate-page"><a href="' . $this->getUrl(['start' => ($i * $this->getRowsPerPage())]) . '"><span>' . ($i + 1) . '</span></a></li>';
                $dot = true;
            } elseif ($dot) {
                $return .= '<li class="rex-navi-paginate-page"><a href="javascript:void(0);"><span>&nbsp;...&nbsp;</span></a></li>';
                $dot = false;
            }
        }

        if ($next !== '') {
            $return .= '<li class="rex-navi-paginate-next"><a class="rex-active" href="' . $this->getUrl(['start' => $next]) . '" title="' . rex_i18n::msg('list_next') . '"><span>' . rex_i18n::msg('list_next') . '</span></a></li>';
        } else {
            $return .= '<li class="rex-navi-paginate-next"><a href="javascript:void(0);"><span>' . rex_i18n::msg('list_next') . '</span></a></li>';
        }

        $return .= '<li class="rex-navi-paginate-message"><span>' . rex_i18n::msg('list_message', $current, $entries, $this->getRows()). '</span></li>';

        $return = '<ul class="rex-navi-paginate">' . $return . '</ul>';
        $return = '<div class="rex-clearer"></div><div class="rex-navi-paginate rex-toolbar"><div class="rex-toolbar-content">' . $return . '<div class="rex-clearer"></div></div></div>';

        return $return;
    }

    /*
    on edit page, only back and forward.
    */

    public function getSingleView($params = [])
    {
        $return = '';

        $current = (int) $_REQUEST[$this->skip_key];
        $current = 0;

        if ($current > $this->getRows() || $current < 0) {
            $current = 0;
        }

        $last = ((int) ($this->getRows() / $this->getRowsPerPage()) * $this->getRowsPerPage()) - $this->getRowsPerPage();
        $next = $current + $this->getRowsPerPage();
        if ($next >= $this->getRows()) {
            $next = '';
        }
        $prev = $current - $this->getRowsPerPage();
        if ($prev < 0) {
            $prev = '';
        }

        $page_current = (int) ($current / $this->getRowsPerPage());
        $page_all = (int) (($this->getRows() - 1) / $this->getRowsPerPage());

        $show_pages = [0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
        if ($page_all > 6) {
            $show_pages = [];
            $show_pages[0] = 0;
            $show_pages[1] = 1;

            if ($page_current < ($page_all / 3) || $page_current > ($page_all / 3 * 2)) {
                $m = (int) ($page_all / 2);
                $show_pages[$m - 1] = $m - 1;
                $show_pages[$m] = $m;
                $show_pages[$m + 1] = $m + 1;
            }

            $show_pages[$page_current - 1] = $page_current - 1;
            $show_pages[$page_current] = $page_current;
            $show_pages[$page_current + 1] = $page_current + 1;

            $show_pages[$page_all - 1] = $page_all - 1;
            $show_pages[$page_all] = $page_all;
        }

        $return .= '<div class="header">';
        $return .= '<ul class="navi-header"><li class="first"><a class="back" href="' . $this->getUrl(array_merge($params, ['list_style' => 'line', 'start' => 0])) . '">'.rex_i18n::msg('yform_back_to_overview').'</a></li></ul>';

        $return .= '<ul class="navi-pagination">';
        if ($page_all > 1) {
            if ($prev !== '') {
                $return .= '<li class="prev"><a class="prev" href="' . $this->getUrl($params, $prev) . '">'.rex_i18n::msg('list_back').'</a></li>';
            } else {
                $return .= '<li class="prev"><a class="prev disabled" href="javascript:void(0);">'.rex_i18n::msg('list_back').'</a></li>';
            }

            if ($next !== '') {
                $return .= '<li class="next"><a class="next" href="' . $this->getUrl($params, $next) . '">'.rex_i18n::msg('list_next').'</a></li>';
            } else {
                $return .= '<li class="next"><a class="next disabled" href="javascript:void(0);">'.rex_i18n::msg('list_next').'</a></li>';
            }
        }
        $return .= '</ul>';
        $return .= '</div>';

        return $return;
    }

    // ---------------------------------------------------

    public function setColumnLabel($columnName, $label)
    {
        $this->columnLabels[$columnName] = rex_i18n::translate($label, null);
    }

    /**
     * Setzt ein Format für die Spalte.
     *
     * @param $columnName Name der Spalte
     * @param $format_type Formatierungstyp
     * @param $format Zu verwendentes Format
     * @param $params Custom params für callback func bei format_type 'custom'
     */
    public function setColumnFormat($columnName, $format_type, $format = '', $params = [])
    {
        $this->columnFormates[$columnName] = [$format_type, $format, $params];
    }

    /**
     * Formatiert einen übergebenen String anhand der reyformatter Klasse.
     *
     * @param $value Zu formatierender String
     * @param $format Array mit den Formatierungsinformationen
     * @param $escape Flag, Ob escapen von $value erlaubt ist
     *
     * @return string
     */
    public function formatValue($value, $format, $escape, $field = null)
    {
        if (is_array($format)) {
            // Callbackfunktion -> Parameterliste aufbauen
            if ($this->isCustomFormat($format)) {
                $format[2] = isset($format[2]) ? $format[2] : [];
                $format[1] = [$format[1], ['list' => $this, 'field' => $field, 'value' => $value, 'format' => $format[0], 'escape' => $escape, 'params' => $format[2]]];
            }

            $value = rex_formatter::format($value, $format[0], $format[1]);
        }

        // Nur escapen, wenn formatter aufgerufen wird, der kein html zurückgeben können soll
        if ($escape && !$this->isCustomFormat($format) && $format[0] != 'rexmedia' && $format[0] != 'rexurl') {
            $value = htmlspecialchars($value);
        }

        return $value;
    }
}
