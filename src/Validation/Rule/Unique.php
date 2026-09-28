<?php

namespace Yakamara\YForm\Validation\Rule;

use LogicException;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Validation\Rule;
use Yakamara\YForm\Validation\ValidationContext;

/**
 * The value must not exist in another row of the same table. `scope` lists further columns that together
 * with the field form the unique key (e.g. `new Unique(scope: ['clang_id'])`).
 *
 * Use `->unique()` on the field to get this rule plus a matching unique index.
 */
final readonly class Unique implements Rule
{
    /** @param list<string> $scope */
    public function __construct(
        public array $scope = [],
        public ?string $message = null,
    ) {}

    public function validate(mixed $value, Field $field, ValidationContext $context): ?string
    {
        if (null === $context->table) {
            throw new LogicException('The Unique rule needs a table context.');
        }

        $query = $context->table->query();
        foreach ($field->toDb($value) as $column => $dbValue) {
            $query->where($column, $dbValue);
        }
        foreach ($this->scope as $column) {
            $query->where($column, $context->value($column));
        }
        if (null !== $context->record?->id) {
            $query->where('id', '!=', $context->record->id);
        }

        return $query->exists() ? ($this->message ?? I18n::rawMsg('yform_validation_unique', $field->translatedLabel())) : null;
    }
}
