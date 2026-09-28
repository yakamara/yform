<?php

namespace Yakamara\YForm\Field;

use Override;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Validation\ValidationContext;

use function password_get_info;
use function password_hash;

use const PASSWORD_DEFAULT;

/**
 * Password input. The value is never rendered back into the form, and an empty submission keeps the stored value.
 *
 * With `->hashed()` (default) the value is stored with `password_hash()`; verify with `password_verify()`.
 * Attach `new PasswordPolicy()` for strength rules.
 */
class Password extends Text
{
    public protected(set) bool $hashed = true;

    #[Override]
    protected function init(): void
    {
        $this->setInputType('password');
        $this->maxLength(255);
        $this->listed = false;
        $this->sortable = false;
        $this->attributes(['autocomplete' => 'new-password']);
    }

    public function hashed(bool $hashed = true): static
    {
        $this->hashed = $hashed;
        return $this;
    }

    #[Override]
    public function keepsValueOnEmptyInput(): bool
    {
        return true;
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        // An unchanged, already hashed value was validated when it was set.
        if ($this->hashed && null !== $value && null !== password_get_info((string) $value)['algo']) {
            return [];
        }
        return parent::validate($value, $context);
    }

    #[Override]
    public function beforeSave(Record $record, bool $isNew): void
    {
        $value = $record->get($this->name);
        if ($this->hashed && null !== $value && null === password_get_info((string) $value)['algo']) {
            $record->set($this->name, password_hash((string) $value, PASSWORD_DEFAULT));
        }
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return $this->isEmpty($value) ? '' : '••••••';
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        return '';
    }
}
