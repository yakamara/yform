<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Core;
use Yakamara\YForm\Data\Record;

/**
 * Login of the backend user who created the record (frontend: `frontend`, console: `console`). Default name
 * `created_by`.
 */
class CreatedBy extends Text
{
    public static function create(string $name = 'created_by'): static
    {
        return static::new($name);
    }

    #[Override]
    protected function init(): void
    {
        $this->readonly();
        $this->listed = false;
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
            $record->set($this->name, self::currentUser());
        }
    }

    public static function currentUser(): string
    {
        return Core::getUser()?->login ?? (Core::isFrontend() ? 'frontend' : 'console');
    }
}
