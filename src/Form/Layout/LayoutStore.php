<?php

namespace Yakamara\YForm\Form\Layout;

use InvalidArgumentException;
use Redaxo\Core\Filesystem\File;
use ReflectionClass;
use RuntimeException;

use function dirname;
use function is_array;
use function is_file;
use function is_writable;
use function json_decode;
use function json_encode;
use function sprintf;
use function substr;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Reads and writes designer layouts as `ClassName.layout.json` next to the defining class.
 *
 * Keeping the file beside the class means the layout is versioned and deployed together with the definition.
 */
final class LayoutStore
{
    /** @param class-string $class */
    public static function path(string $class): string
    {
        $file = new ReflectionClass($class)->getFileName();
        if (false === $file) {
            throw new InvalidArgumentException(sprintf('Class "%s" has no source file.', $class));
        }
        return substr($file, 0, -4) . '.layout.json';
    }

    /** @param class-string $class */
    public static function load(string $class): ?Layout
    {
        $path = self::path($class);
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) File::get($path), true);
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Layout file "%s" contains invalid JSON.', $path));
        }
        return Layout::fromArray($data);
    }

    /** @param class-string $class */
    public static function isWritable(string $class): bool
    {
        $path = self::path($class);
        return is_file($path) ? is_writable($path) : is_writable(dirname($path));
    }

    /** @param class-string $class */
    public static function save(string $class, Layout $layout): void
    {
        $json = json_encode($layout->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (!File::put(self::path($class), $json . "\n")) {
            throw new RuntimeException(sprintf('Layout file "%s" could not be written.', self::path($class)));
        }
    }

    /** @param class-string $class */
    public static function delete(string $class): void
    {
        File::delete(self::path($class));
    }

    /** @param class-string $class */
    public static function exists(string $class): bool
    {
        return is_file(self::path($class));
    }
}
