<?php

namespace Yakamara\YForm\Field\Upload;

use Yakamara\YForm\Field\Upload;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;
use function move_uploaded_file;
use function rename;

use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * One file of the request (`$_FILES`), as handed to {@see Upload::fromInput()}.
 *
 * Only objects can carry a file into a field: a posted string or array never does, so a client can not point a
 * field at an arbitrary path on the server.
 */
final readonly class UploadedFile
{
    public function __construct(
        public string $name,
        public string $path,
        public int $size,
        public int $error = UPLOAD_ERR_OK,
        /** Whether the file comes from `$_FILES` and must be moved with `move_uploaded_file()`. */
        public bool $fromRequest = false,
    ) {}

    public function isEmpty(): bool
    {
        return UPLOAD_ERR_NO_FILE === $this->error;
    }

    public function moveTo(string $target): bool
    {
        return $this->fromRequest ? move_uploaded_file($this->path, $target) : rename($this->path, $target);
    }

    /**
     * Turns the PHP structure of `$_FILES[$name]` (separate trees for `name`, `tmp_name`, …) into one tree with an
     * object per file, matching the structure of the posted values: `form[cv][upload][]` becomes
     * `['cv' => ['upload' => [UploadedFile]]]`.
     *
     * @param array<mixed> $files
     *
     * @return array<mixed>
     */
    public static function tree(array $files): array
    {
        if (!array_key_exists('name', $files) || !array_key_exists('tmp_name', $files)) {
            return [];
        }
        return self::branch($files['name'], $files['tmp_name'], $files['size'] ?? [], $files['error'] ?? []);
    }

    /** @return array<mixed> */
    private static function branch(mixed $name, mixed $path, mixed $size, mixed $error): array
    {
        $tree = [];
        foreach (is_array($name) ? $name : [] as $key => $child) {
            $childPath = is_array($path) ? ($path[$key] ?? null) : null;
            $childSize = is_array($size) ? ($size[$key] ?? 0) : 0;
            $childError = is_array($error) ? ($error[$key] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
            if (is_array($child)) {
                $tree[$key] = self::branch($child, $childPath, $childSize, $childError);
            } elseif (is_string($child) && is_string($childPath)) {
                $tree[$key] = new self($child, $childPath, is_int($childSize) ? $childSize : 0, is_int($childError) ? $childError : UPLOAD_ERR_NO_FILE, true);
            }
        }
        return $tree;
    }
}
