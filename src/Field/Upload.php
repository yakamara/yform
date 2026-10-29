<?php

namespace Yakamara\YForm\Field;

use InvalidArgumentException;
use Override;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\MediaPool\Media as MediaPoolMedia;
use Redaxo\Core\MediaPool\MediaHandler;
use Redaxo\Core\MediaPool\MediaPool;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\Util\Formatter;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\Upload\PendingUpload;
use Yakamara\YForm\Field\Upload\UploadedFile;
use Yakamara\YForm\Field\Upload\UploadStorage;
use Yakamara\YForm\Validation\ValidationContext;

use function array_map;
use function array_values;
use function basename;
use function copy;
use function implode;
use function in_array;
use function ini_get;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;
use function json_encode;
use function pathinfo;
use function preg_match;
use function rename;
use function str_starts_with;
use function strtolower;
use function strtoupper;
use function substr;
use function unlink;

use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_OK;

/**
 * File upload — in the backend and in frontend forms. Single file as `varchar`, `->multiple()` as JSON array in
 * `text`; the column holds the file names.
 *
 * By default the files are stored outside the web root (`data/addons/yform/upload/<table>/<field>/<id>/`) and are
 * only delivered through the backend; projects read them with {@see Path()}. With `->toMediaPool()` they are added
 * to the media pool instead and the field behaves like a {@see Media} field afterwards.
 *
 * ```php
 * yield Upload::new('cv')->label('Lebenslauf')->types('pdf', 'docx')->maxSize('5M');
 * yield Upload::new('images')->multiple()->types('jpg', 'png')->toMediaPool(category: 3);
 * ```
 *
 * A form that fails validation keeps the uploaded files (they wait under a random token), so the visitor does not
 * have to select them again.
 */
class Upload extends Field
{
    public protected(set) bool $multiple = false;

    /** @var list<string> */
    public protected(set) array $types = [];

    /** Maximum size per file in bytes, `null` = only the PHP limit (`upload_max_filesize`). */
    public protected(set) ?int $maxSize = null;

    public protected(set) bool $mediaPool = false;
    public protected(set) ?int $mediaCategory = null;

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /** Allowed file extensions, e.g. `->types('pdf', 'docx')`; without it every extension is accepted. */
    public function types(string ...$types): static
    {
        $this->types = array_values(array_map(static fn (string $type): string => strtolower(ltrim($type, '.')), $types));
        return $this;
    }

    /** Maximum size per file: bytes or a string like `'500K'`, `'5M'`, `'1G'`. */
    public function maxSize(int|string|null $maxSize): static
    {
        $this->maxSize = is_string($maxSize) ? self::parseSize($maxSize) : $maxSize;
        return $this;
    }

    /**
     * Adds the uploaded files to the media pool instead of the protected upload folder. The media pool applies its
     * own rules on top of {@see types()} (blocked extensions, mime type check) and may rename a file.
     */
    public function toMediaPool(?int $category = null, bool $enabled = true): static
    {
        $this->mediaPool = $enabled;
        $this->mediaCategory = $category;
        return $this;
    }

    // ---------------------------------------------------------------- storage

    #[Override]
    protected function columnType(): string
    {
        return $this->multiple ? 'text' : 'varchar(191)';
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        if (!$this->multiple) {
            return null === $raw || '' === $raw ? null : (string) $raw;
        }
        $values = null === $raw || '' === $raw ? [] : json_decode((string) $raw, true);
        return is_array($values) ? array_values(array_map('strval', $values)) : [];
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        $names = array_values($this->storedNames($this->entries($value)));
        if (!$this->multiple) {
            return [$this->name => $names[0] ?? null];
        }
        return [$this->name => [] === $names ? null : json_encode($names, JSON_UNESCAPED_UNICODE)];
    }

    #[Override]
    public function defaultValue(): mixed
    {
        return $this->multiple ? [] : null;
    }

    /**
     * The value as list: stored file names and uploads waiting for the record.
     *
     * @return list<string|PendingUpload>
     */
    public function entries(mixed $value): array
    {
        $entries = [];
        foreach (is_array($value) ? $value : [$value] as $entry) {
            if ((is_string($entry) && '' !== $entry) || $entry instanceof PendingUpload) {
                $entries[] = $entry;
            }
        }
        return $entries;
    }

    /** @return list<string> names of the stored files (waiting uploads are not included) */
    public function fileNames(mixed $value): array
    {
        $names = [];
        foreach ($this->entries($value) as $entry) {
            if (is_string($entry)) {
                $names[] = $entry;
            }
        }
        return $names;
    }

    /**
     * Absolute path of a file of the value, `null` when it does not exist: the stored file of the record, the media
     * pool file or the waiting upload.
     */
    public function path(?Record $record, string|PendingUpload $entry): ?string
    {
        if ($entry instanceof PendingUpload) {
            $path = $entry->isRejected() ? null : $entry->path();
        } elseif ($this->mediaPool) {
            $path = null === MediaPoolMedia::get($entry) ? null : Path::media($entry);
        } else {
            $path = null === $record?->id || !self::isFileName($entry) ? null : UploadStorage::recordDir($record->table->name, $this->name, $record->id) . $entry;
        }
        return null !== $path && is_file($path) ? $path : null;
    }

    /**
     * File names the value is stored with, by entry index. Waiting uploads get a name that is unique next to the
     * kept files; the result is the same on every call, so {@see toDb()} and {@see afterSave()} agree.
     *
     * @param list<string|PendingUpload> $entries
     *
     * @return array<int, string>
     */
    private function storedNames(array $entries): array
    {
        $taken = [];
        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $taken[] = $entry;
            }
        }
        $names = [];
        foreach ($entries as $index => $entry) {
            if (is_string($entry)) {
                $names[$index] = $entry;
            } elseif (!$entry->isRejected()) {
                $names[$index] = $taken[] = UploadStorage::uniqueName($entry->name, $taken);
            }
        }
        return $names;
    }

    /** Moves the waiting uploads to their final place and removes files that are no longer part of the value. */
    #[Override]
    public function afterSave(Record $record, bool $isNew): void
    {
        $entries = $this->entries($record->get($this->name));
        $names = $this->storedNames($entries);
        $id = (int) $record->id;

        if ($this->mediaPool) {
            $changed = false;
            foreach ($entries as $index => $entry) {
                if (!$entry instanceof PendingUpload || $entry->isRejected()) {
                    continue;
                }
                // the handler derives the file name from the given file, so the upload is renamed to its final name first
                $tmpDir = UploadStorage::tmpDir($entry->token);
                $source = $tmpDir . $names[$index];
                if ($source !== $entry->path() && !rename($entry->path(), $source)) {
                    throw new RuntimeException('Upload "' . $entry->name . '" could not be stored.');
                }
                $media = MediaHandler::add([
                    'category_id' => $this->mediaCategory,
                    'title' => pathinfo($entry->name, PATHINFO_FILENAME),
                    'file' => new File($source),
                ], [], true, $this->types);
                UploadStorage::deleteDir($tmpDir);
                $changed = $changed || $media->fileName !== $names[$index];
                $names[$index] = $media->fileName;
            }
            $value = $this->multiple ? array_values($names) : (array_values($names)[0] ?? null);
            if ($changed) {
                Sql::factory()->setTable($record->table->name)->setWhere(['id' => $id])->setValues($this->toDb($value))->update();
            }
            $record->set($this->name, $value);
            return;
        }

        $dir = UploadStorage::recordDir($record->table->name, $this->name, $id);
        foreach ($entries as $index => $entry) {
            if (!$entry instanceof PendingUpload || $entry->isRejected()) {
                continue;
            }
            if (!UploadStorage::createDir($dir) || !rename($entry->path(), $dir . $names[$index])) {
                throw new RuntimeException('Upload "' . $entry->name . '" could not be stored.');
            }
            UploadStorage::deleteDir(UploadStorage::tmpDir($entry->token));
        }
        foreach (UploadStorage::files($dir) as $file) {
            if (!in_array($file, $names, true)) {
                unlink($dir . $file);
            }
        }
        if ([] === $names) {
            UploadStorage::deleteDir($dir);
        }

        $record->set($this->name, $this->multiple ? array_values($names) : (array_values($names)[0] ?? null));
    }

    /** Files in the protected folder belong to the record; media pool files stay, they may be used elsewhere. */
    #[Override]
    public function beforeDelete(Record $record): void
    {
        if (!$this->mediaPool && null !== $record->id) {
            UploadStorage::deleteDir(UploadStorage::recordDir($record->table->name, $this->name, $record->id));
        }
    }

    /** A copy gets its own copies of the files; media pool files are shared. */
    #[Override]
    public function cloneValue(mixed $value, Record $source): mixed
    {
        if (!$this->cloneable) {
            return $this->defaultValue();
        }
        if ($this->mediaPool) {
            return $value;
        }
        $copies = [];
        foreach ($this->entries($value) as $entry) {
            $path = $this->path($source, $entry);
            if (null === $path) {
                continue;
            }
            $name = is_string($entry) ? $entry : $entry->name;
            $token = UploadStorage::newToken();
            if (UploadStorage::createDir(UploadStorage::tmpDir($token)) && copy($path, UploadStorage::tmpDir($token) . $name)) {
                $copies[] = new PendingUpload($token, $name, (int) filesize($path));
            }
        }
        return $this->multiple ? $copies : ($copies[0] ?? null);
    }

    // ---------------------------------------------------------------- input

    /**
     * The control submits
     *  - `files[]`: the entries to keep (`k:name` for stored files, `p:token` for waiting uploads),
     *  - `remove[]`: entries the user ticked for removal,
     *  - `upload` / `upload[]`: new files ({@see UploadedFile}, merged in from `$_FILES` by the form).
     *
     * In a single-file field a new upload replaces the kept file.
     */
    #[Override]
    public function fromInput(mixed $input): mixed
    {
        if (!is_array($input)) {
            return $this->defaultValue();
        }

        $remove = array_filter(is_array($input['remove'] ?? null) ? $input['remove'] : [], 'is_string');
        $entries = [];
        foreach (is_array($input['files'] ?? null) ? $input['files'] : [] as $key) {
            if (is_string($key) && !in_array($key, $remove, true) && null !== $entry = $this->entryFromKey($key)) {
                $entries[$key] = $entry;
            }
        }
        $entries = array_values($entries);

        $uploads = $input['upload'] ?? [];
        $new = [];
        foreach ($uploads instanceof UploadedFile ? [$uploads] : (is_array($uploads) ? $uploads : []) as $file) {
            if ($file instanceof UploadedFile && !$file->isEmpty()) {
                $new[] = $this->accept($file);
            }
        }

        if ($this->multiple) {
            return [...$entries, ...$new];
        }
        if ([] === $new) {
            return $entries[0] ?? null;
        }
        $upload = $new[0];
        if ($upload->isRejected() && isset($entries[0])) {
            // Keep the previous file visible, the field shows the error.
            return PendingUpload::rejected($upload->name, (string) $upload->error, self::key($entries[0]));
        }
        return $upload;
    }

    /** Identifier of an entry in the submitted `files[]` list. */
    public static function key(string|PendingUpload $entry): string
    {
        return is_string($entry) ? 'k:' . $entry : $entry->key();
    }

    public function entryFromKey(string $key): string|PendingUpload|null
    {
        $value = substr($key, 2);
        return match (substr($key, 0, 2)) {
            'k:' => self::isFileName($value) ? $value : null,
            'p:' => UploadStorage::restore($value),
            default => null,
        };
    }

    /** Checks a new file and moves it into a waiting directory. */
    private function accept(UploadedFile $file): PendingUpload
    {
        $name = UploadStorage::safeName($file->name);
        $label = $this->translatedLabel();

        if (UPLOAD_ERR_INI_SIZE === $file->error || UPLOAD_ERR_FORM_SIZE === $file->error) {
            return PendingUpload::rejected($name, I18n::rawMsg('yform_validation_upload_size', $label, $name, Formatter::bytes($this->maxSize ?? self::phpLimit())));
        }
        if (UPLOAD_ERR_OK !== $file->error) {
            return PendingUpload::rejected($name, I18n::rawMsg('yform_validation_upload_failed', $label, $name));
        }
        if (null !== $this->maxSize && $file->size > $this->maxSize) {
            return PendingUpload::rejected($name, I18n::rawMsg('yform_validation_upload_size', $label, $name, Formatter::bytes($this->maxSize)));
        }
        if ([] !== $this->types && !in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $this->types, true)) {
            return PendingUpload::rejected($name, I18n::rawMsg('yform_validation_media_type', $label, implode(', ', $this->types)));
        }

        UploadStorage::collectGarbage();
        $token = UploadStorage::newToken();
        $dir = UploadStorage::tmpDir($token);
        if (!UploadStorage::createDir($dir) || !$file->moveTo($dir . $name)) {
            UploadStorage::deleteDir($dir);
            return PendingUpload::rejected($name, I18n::rawMsg('yform_validation_upload_failed', $label, $name));
        }
        return new PendingUpload($token, $name, (int) filesize($dir . $name));
    }

    // ---------------------------------------------------------------- validation

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        $errors = parent::validate($value, $context);
        $label = $this->translatedLabel();
        $record = $context->record;

        foreach ($this->entries($value) as $entry) {
            if ($entry instanceof PendingUpload) {
                if ($entry->isRejected()) {
                    $errors[] = (string) $entry->error;
                } elseif ($this->mediaPool && (!MediaPool::isAllowedExtension($entry->name, $this->types) || !MediaPool::isAllowedMimeType($entry->path(), $entry->name))) {
                    $errors[] = I18n::rawMsg('yform_validation_upload_media_pool', $label, $entry->name);
                }
                continue;
            }
            if ($this->mediaPool) {
                if (null === MediaPoolMedia::get($entry)) {
                    $errors[] = I18n::rawMsg('yform_validation_media_missing', $label, $entry);
                }
            } elseif (null === $this->path($record, $entry)) {
                // Only files stored with this record can be kept; anything else is a tampered or outdated name.
                $errors[] = I18n::rawMsg('yform_validation_upload_missing', $label, $entry);
            }
        }
        return $errors;
    }

    // ---------------------------------------------------------------- output

    #[Override]
    public function textValue(mixed $value): string
    {
        $names = [];
        foreach ($this->entries($value) as $entry) {
            if (is_string($entry)) {
                $names[] = $entry;
            } elseif (!$entry->isRejected()) {
                $names[] = $entry->name;
            }
        }
        return implode(', ', $names);
    }

    // ---------------------------------------------------------------- helpers

    /** A plain file name without directory parts, as produced by {@see UploadStorage::safeName()} or the media pool. */
    private static function isFileName(string $name): bool
    {
        return '' !== $name && basename($name) === $name && !str_starts_with($name, '.') && 1 !== preg_match('/[\/\\\\\x00]/', $name);
    }

    private static function parseSize(string $size): int
    {
        if (1 !== preg_match('/^\s*(\d+(?:\.\d+)?)\s*([KMG]?)B?\s*$/i', $size, $match)) {
            throw new InvalidArgumentException('Invalid size "' . $size . '", use e.g. "500K", "5M" or bytes.');
        }
        $factor = match (strtoupper($match[2])) {
            'K' => 1024,
            'M' => 1024 ** 2,
            'G' => 1024 ** 3,
            default => 1,
        };
        return (int) ((float) $match[1] * $factor);
    }

    private static function phpLimit(): int
    {
        $limit = (string) ini_get('upload_max_filesize');
        return '' === $limit ? 0 : self::parseSize($limit);
    }
}
