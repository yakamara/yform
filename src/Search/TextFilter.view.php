<?php

use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Search\TextFilter;

return static function (TextFilter $filter, string $name, string $id, mixed $input): void {
    echo '<input ', new HtmlAttributes([
        'class' => 'rex-input',
        'type' => 'text',
        'name' => $name,
        'id' => $id,
        'value' => is_string($input) ? $input : '',
    ]), '>';
};
