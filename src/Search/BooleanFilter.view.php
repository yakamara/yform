<?php

use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Search\BooleanFilter;

return static function (BooleanFilter $filter, string $name, string $id, mixed $input): void {
    echo '<select ', new HtmlAttributes(['class' => 'rex-input', 'name' => $name, 'id' => $id]), '>';
    foreach (['' => 'yform_filter_any', '1' => 'yform_yes', '0' => 'yform_no'] as $value => $label) {
        echo '<option ', new HtmlAttributes(['value' => (string) $value, 'selected' => (string) $value === $input]), '>', I18n::msg($label), '</option>';
    }
    echo '</select>';
};
