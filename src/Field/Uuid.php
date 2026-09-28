<?php

namespace Yakamara\YForm\Field;

use Override;
use Yakamara\YForm\Data\Record;

use function bin2hex;
use function chr;
use function ord;
use function random_bytes;
use function str_split;

/** UUID v4, generated on first save, read-only and unique. */
class Uuid extends Text
{
    #[Override]
    protected function init(): void
    {
        $this->maxLength(36);
        $this->readonly();
        $this->unique();
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
        if (null === $record->get($this->name)) {
            $record->set($this->name, self::generate());
        }
    }

    public static function generate(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
