<?php

namespace Yakamara\YForm\Attribute;

use Attribute;

/**
 * Registers a value field under the type name used in form definitions and in
 * `rex_yform_field.type_name`.
 *
 * The name is the persisted contract: `#[AsValue('text')]` is what makes the pipe notation line
 * `text|name|Label|` and the stored field row `type_id=value, type_name=text` resolve to this class.
 * It therefore must not change when the class is renamed or moved.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsValue
{
    public function __construct(
        public string $name,
    ) {}
}
