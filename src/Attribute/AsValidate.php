<?php

namespace Yakamara\YForm\Attribute;

use Attribute;

/**
 * Registers a validate field under the type name used in form definitions and in
 * `rex_yform_field.type_name` (with `type_id=validate`).
 *
 * @see AsValue for why the name is a persisted contract
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsValidate
{
    public function __construct(
        public string $name,
    ) {}
}
