<?php

namespace Yakamara\YForm\Attribute;

use Attribute;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Table\Table;

/**
 * Marks a {@see Table} subclass as managed table.
 *
 * Arguments must be constant expressions (they are cached by the class discovery).
 *
 * ```php
 * #[AsTable('rex_product', label: 'Produkte', icon: 'fa-box')]
 * final class ProductTable extends Table { … }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsTable
{
    /**
     * @param string $name database table name, e.g. `rex_product`
     * @param string|null $label backend label, `translate:key` supported
     * @param string|null $icon Font Awesome icon class for the backend navigation
     * @param class-string<Record>|null $model record class for rows of this table
     * @param int $prio position in the backend navigation
     * @param bool $navigation whether the table gets its own entry in the backend navigation
     */
    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?string $icon = null,
        public ?string $model = null,
        public int $prio = 0,
        public bool $navigation = true,
    ) {}
}
