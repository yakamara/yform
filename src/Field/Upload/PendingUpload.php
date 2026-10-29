<?php

namespace Yakamara\YForm\Field\Upload;

/**
 * A file that has been uploaded but not yet stored with a record. It waits in a temporary directory under a random
 * token, so a form that fails validation for another field keeps the file without uploading it again.
 *
 * A rejected upload (too large, wrong type) carries the `error` message and has no file.
 */
final readonly class PendingUpload
{
    public function __construct(
        public string $token,
        public string $name,
        public int $size,
        public ?string $error = null,
        /** The entry a rejected upload would have replaced in a single-file field (`k:name` or `p:token`). */
        public ?string $previous = null,
    ) {}

    public static function rejected(string $name, string $error, ?string $previous = null): self
    {
        return new self('', $name, 0, $error, $previous);
    }

    public function isRejected(): bool
    {
        return null !== $this->error;
    }

    /** Absolute path of the waiting file. */
    public function path(): string
    {
        return UploadStorage::tmpDir($this->token) . $this->name;
    }

    /** Identifier submitted with the form to keep the file (`p:token`). */
    public function key(): string
    {
        return 'p:' . $this->token;
    }
}
