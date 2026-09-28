<?php

namespace Yakamara\YForm\Validation;

use Countable;

use function array_merge;
use function array_values;
use function count;

/**
 * Validation messages per field name; the empty key `''` holds messages not bound to a field.
 */
final class Errors implements Countable
{
    /** @var array<string, list<string>> */
    private array $messages = [];

    public function add(string $field, string $message): void
    {
        $this->messages[$field][] = $message;
    }

    /** Adds a message that is not bound to a single field. */
    public function addGlobal(string $message): void
    {
        $this->add('', $message);
    }

    /** @return list<string> */
    public function for(string $field): array
    {
        return $this->messages[$field] ?? [];
    }

    public function has(string $field): bool
    {
        return isset($this->messages[$field]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->messages;
    }

    /** @return list<string> */
    public function all(): array
    {
        return array_values(array_merge(...array_values($this->messages)));
    }

    /** @return array<string, list<string>> */
    public function toArray(): array
    {
        return $this->messages;
    }

    public function count(): int
    {
        return count($this->all());
    }
}
