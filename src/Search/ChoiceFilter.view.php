<?php

use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Search\ChoiceFilter;

use function Redaxo\Core\View\escape;

return static function (ChoiceFilter $filter, string $name, string $id, mixed $input): void {
    $selected = array_map('strval', is_array($input) ? $input : [$input]);
    echo '<select ', new HtmlAttributes([
        'class' => 'rex-input',
        'name' => $name . '[]',
        'id' => $id,
        'multiple' => true,
        'data-rex-select' => true,
        'data-rex-select-search' => count($filter->options) > 10,
        'data-rex-select-placeholder' => I18n::rawMsg('yform_filter_any'),
    ]), '>';
    foreach ($filter->options as $value => $label) {
        echo '<option ', new HtmlAttributes(['value' => (string) $value, 'selected' => in_array((string) $value, $selected, true)]), '>', escape($label), '</option>';
    }
    echo '</select>';
};
