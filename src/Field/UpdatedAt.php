<?php

namespace Yakamara\YForm\Field;

use DateTimeImmutable;
use Override;
use Yakamara\YForm\Data\Record;

/** Timestamp of the last change, set automatically on every save. Default name `updated_at`. */
class UpdatedAt extends CreatedAt
{
    #[Override]
    public static function create(string $name = 'updated_at'): static
    {
        return static::new($name);
    }

    #[Override]
    public function beforeSave(Record $record, bool $isNew): void
    {
        $record->set($this->name, new DateTimeImmutable());
    }
}
