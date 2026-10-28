<?php

use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Search\RangeFilter;

return static function (RangeFilter $filter, string $name, string $id, mixed $input): void {
    $input = is_array($input) ? $input : [];
    $type = 'decimal' === $filter->inputType ? 'text' : $filter->inputType;
    echo '<div class="yform-filter-range">';
    foreach (['from', 'to'] as $bound) {
        $value = $input[$bound] ?? '';
        echo '<input ', new HtmlAttributes([
            'class' => 'rex-input',
            'type' => $type,
            'inputmode' => 'decimal' === $filter->inputType ? 'decimal' : null,
            'step' => 'number' === $type ? 'any' : null,
            'name' => $name . '[' . $bound . ']',
            'id' => 'from' === $bound ? $id : null,
            'value' => is_string($value) ? $value : '',
            'placeholder' => I18n::rawMsg('yform_filter_' . $bound),
            'aria-label' => I18n::rawMsg('yform_filter_' . $bound),
        ]), '>';
    }
    echo '</div>';
};
