<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test;

use Countable;
use Throwable;
use Yakamara\YForm\Test\Exception\AssertionFailedException;

use function array_key_exists;
use function count;
use function gettype;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;

/**
 * In-house assertion library used by AbstractTestSuite.
 *
 * Keeps PHPUnit out of the runtime path so the test commands can run on any
 * REDAXO install without a dev dependency.
 *
 * @package redaxo\yform
 * @internal
 */
final class Assert
{
    public static function same(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionFailedException(($msg ?: 'Failed asserting two values are identical.') . "\n  expected: " . self::dump($expected) . "\n  actual:   " . self::dump($actual));
        }
    }

    public static function notSame(mixed $unexpected, mixed $actual, string $msg = ''): void
    {
        if ($unexpected === $actual) {
            throw new AssertionFailedException(($msg ?: 'Failed asserting two values are not identical.') . "\n  value:    " . self::dump($actual));
        }
    }

    public static function equals(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected != $actual) {
            throw new AssertionFailedException(($msg ?: 'Failed asserting two values are equal.') . "\n  expected: " . self::dump($expected) . "\n  actual:   " . self::dump($actual));
        }
    }

    public static function true(bool $cond, string $msg = ''): void
    {
        if (!$cond) {
            throw new AssertionFailedException($msg ?: 'Failed asserting that condition is true.');
        }
    }

    public static function false(bool $cond, string $msg = ''): void
    {
        if ($cond) {
            throw new AssertionFailedException($msg ?: 'Failed asserting that condition is false.');
        }
    }

    public static function null(mixed $value, string $msg = ''): void
    {
        if (null !== $value) {
            throw new AssertionFailedException(($msg ?: 'Failed asserting null.') . "\n  actual: " . self::dump($value));
        }
    }

    public static function notNull(mixed $value, string $msg = ''): void
    {
        if (null === $value) {
            throw new AssertionFailedException($msg ?: 'Failed asserting not-null.');
        }
    }

    public static function count(int $expected, Countable|array $actual, string $msg = ''): void
    {
        $c = is_array($actual) ? count($actual) : $actual->count();
        if ($c !== $expected) {
            throw new AssertionFailedException(($msg ?: 'Failed asserting count.') . " expected={$expected} actual={$c}");
        }
    }

    /**
     * @param class-string $class
     */
    public static function instanceOf(string $class, mixed $actual, string $msg = ''): void
    {
        if (!($actual instanceof $class)) {
            $got = is_object($actual) ? $actual::class : gettype($actual);
            throw new AssertionFailedException(($msg ?: 'Failed asserting instance.') . " expected={$class} got={$got}");
        }
    }

    /**
     * @param class-string<Throwable> $exceptionClass
     */
    public static function throws(string $exceptionClass, callable $fn, string $msg = ''): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            if (!($e instanceof $exceptionClass)) {
                throw new AssertionFailedException(($msg ?: 'Wrong exception type.') . " expected={$exceptionClass} got=" . $e::class . "\n  message: " . $e->getMessage());
            }
            return;
        }
        throw new AssertionFailedException(($msg ?: 'No exception was thrown.') . " expected={$exceptionClass}");
    }

    public static function stringContains(string $needle, string $haystack, string $msg = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new AssertionFailedException(($msg ?: 'Failed asserting string contains.') . "\n  needle:   " . self::dump($needle) . "\n  haystack: " . self::dump($haystack));
        }
    }

    public static function arrayHasKey(int|string $key, array $array, string $msg = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new AssertionFailedException(($msg ?: 'Failed asserting array has key.') . ' key=' . self::dump($key));
        }
    }

    public static function arrayNotHasKey(int|string $key, array $array, string $msg = ''): void
    {
        if (array_key_exists($key, $array)) {
            throw new AssertionFailedException(($msg ?: 'Failed asserting array does not have key.') . ' key=' . self::dump($key));
        }
    }

    /**
     * Truncated, single-line representation for error messages.
     */
    private static function dump(mixed $value): string
    {
        if (is_string($value)) {
            $v = mb_strlen($value) > 80 ? mb_substr($value, 0, 77) . '...' : $value;
            return '"' . $v . '"';
        }
        if (is_array($value)) {
            $json = (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $json = mb_strlen($json) > 120 ? mb_substr($json, 0, 117) . '...' : $json;
            return '[' . count($value) . '] ' . $json;
        }
        if (is_object($value)) {
            return $value::class . '#' . spl_object_id($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (null === $value) {
            return 'null';
        }
        return var_export($value, true);
    }
}
