<?php

namespace Yakamara\YForm\Field;

use DateTimeImmutable;
use Override;
use Yakamara\YForm\Data\Record;

/** Timestamp of the creation, set automatically. Default name `created_at`. */
class CreatedAt extends DateTime
{
    public static function create(string $name = 'created_at'): static
    {
        return static::new($name);
    }

    #[Override]
    protected function init(): void
    {
        $this->readonly();
        $this->cloneable = false;
    }

    #[Override]
    public function isEditable(): bool
    {
        return false;
    }

    #[Override]
    public function beforeSave(Record $record, bool $isNew): void
    {
        if ($isNew && null === $record->get($this->name)) {
            $record->set($this->name, new DateTimeImmutable());
        }
    }
}
