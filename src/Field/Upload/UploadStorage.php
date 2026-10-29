<?php

namespace Yakamara\YForm\Field\Upload;

use FilesystemIterator;
use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\Util\Str;

use function bin2hex;
use function filemtime;
use function in_array;
use function is_dir;
use function is_file;
use function mb_strlen;
use function mb_substr;
use function mkdir;
use function preg_match;
use function random_bytes;
use function rmdir;
use function scandir;
use function strrpos;
use function substr;
use function time;
use function unlink;

/**
 * File system layout of the upload field. Everything lives in the data folder of the addon, which is not served
 * by the web server:
 *
 *  - `upload/<table>/<field>/<id>/<file>`: files of saved records
 *  - `upload/_tmp/<token>/<file>`: uploads waiting for their record (removed after a day)
 *
 * @internal
 */
final class UploadStorage
{
    /** Seconds an unsaved upload is kept. */
    public const int TMP_LIFETIME = 86400;

    public static function root(): string
    {
        return Path::addonData('yform', 'upload/');
    }

    public static function tmpDir(string $token): string
    {
        return self::root() . '_tmp/' . $token . '/';
    }

    public static function recordDir(string $table, string $field, int $id): string
    {
        return self::root() . $table . '/' . $field . '/' . $id . '/';
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function isToken(string $token): bool
    {
        return 1 === preg_match('/^[a-f0-9]{32}$/', $token);
    }

    /** A waiting upload by its token, `null` when the token is invalid or expired. */
    public static function restore(string $token): ?PendingUpload
    {
        if (!self::isToken($token)) {
            return null;
        }
        foreach (self::files(self::tmpDir($token)) as $name) {
            $path = self::tmpDir($token) . $name;
            return new PendingUpload($token, $name, (int) filesize($path));
        }
        return null;
    }

    /**
     * File name that is safe on every file system: lowercase ASCII, digits and `._-@`, no leading dot, at most
     * 150 characters (the extension is kept).
     */
    public static function safeName(string $name): string
    {
        $name = Str::normalize(basename(str_replace('\\', '/', $name)), '_', '.-@');
        $name = ltrim($name, '.');
        if ('' === $name) {
            return 'file';
        }
        if (mb_strlen($name) > 150) {
            $dot = strrpos($name, '.');
            $extension = false === $dot ? '' : mb_substr(substr($name, $dot), 0, 20);
            $name = mb_substr($name, 0, 150 - mb_strlen($extension)) . $extension;
        }
        return $name;
    }

    /** `$name`, or `name_1.ext`, `name_2.ext`, … when it is already in `$taken`. */
    public static function uniqueName(string $name, array $taken): string
    {
        if (!in_array($name, $taken, true)) {
            return $name;
        }
        $dot = strrpos($name, '.');
        $base = false === $dot || 0 === $dot ? $name : substr($name, 0, $dot);
        $extension = false === $dot || 0 === $dot ? '' : substr($name, $dot);
        $i = 1;
        while (in_array($candidate = $base . '_' . $i . $extension, $taken, true)) {
            ++$i;
        }
        return $candidate;
    }

    /** @return list<string> names of the regular files in a directory */
    public static function files(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $files = [];
        foreach (scandir($dir) ?: [] as $entry) {
            if ('.' !== $entry && '..' !== $entry && is_file($dir . $entry)) {
                $files[] = $entry;
            }
        }
        return $files;
    }

    public static function createDir(string $dir): bool
    {
        return is_dir($dir) || mkdir($dir, 0o777, true) || is_dir($dir);
    }

    public static function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new FilesystemIterator($dir) as $entry) {
            $entry->isDir() && !$entry->isLink() ? self::deleteDir($entry->getPathname() . '/') : unlink($entry->getPathname());
        }
        rmdir($dir);
    }

    /** Removes waiting uploads older than {@see TMP_LIFETIME}. */
    public static function collectGarbage(): void
    {
        $tmp = self::root() . '_tmp/';
        if (!is_dir($tmp)) {
            return;
        }
        $limit = time() - self::TMP_LIFETIME;
        foreach (new FilesystemIterator($tmp) as $entry) {
            if ($entry->isDir() && self::isToken($entry->getFilename()) && (filemtime($entry->getPathname()) ?: 0) < $limit) {
                self::deleteDir($entry->getPathname() . '/');
            }
        }
    }
}
