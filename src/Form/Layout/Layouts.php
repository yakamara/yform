<?php

namespace Yakamara\YForm\Form\Layout;

use Yakamara\YForm\Table\Table;

/**
 * Effective layout lookup: designer file, then the layout defined in code.
 */
final class Layouts
{
    public static function forTable(Table $table): Layout
    {
        return LayoutStore::load($table::class) ?? $table->layout(new Layout());
    }
}
