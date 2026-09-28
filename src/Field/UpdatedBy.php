<?php

namespace Yakamara\YForm\Field;

use Override;
use Yakamara\YForm\Data\Record;

/** Login of the user who changed the record last. Default name `updated_by`. */
class UpdatedBy extends CreatedBy
{
    #[Override]
    public static function create(string $name = 'updated_by'): static
    {
        return static::new($name);
    }

    #[Override]
    public function beforeSave(Record $record, bool $isNew): void
    {
        $record->set($this->name, self::currentUser());
    }
}
