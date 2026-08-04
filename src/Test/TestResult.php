<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test;

use Throwable;

/**
 * One outcome of one test method.
 *
 * @package redaxo\yform
 * @internal
 */
final class TestResult
{
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ERRORED = 'errored';
    public const STATUS_SKIPPED = 'skipped';

    private function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly int $durationMs,
        public readonly ?string $message = null,
        public readonly ?Throwable $throwable = null,
    ) {}

    public static function passed(string $name, int $durationMs): self
    {
        return new self($name, self::STATUS_PASSED, $durationMs);
    }

    public static function failed(string $name, int $durationMs, Throwable $e): self
    {
        return new self($name, self::STATUS_FAILED, $durationMs, $e->getMessage(), $e);
    }

    public static function errored(string $name, int $durationMs, Throwable $e): self
    {
        return new self($name, self::STATUS_ERRORED, $durationMs, $e->getMessage(), $e);
    }

    public static function skipped(string $name, string $reason): self
    {
        return new self($name, self::STATUS_SKIPPED, 0, $reason);
    }

    public function isPassed(): bool
    {
        return self::STATUS_PASSED === $this->status;
    }

    public function isFailedOrErrored(): bool
    {
        return self::STATUS_FAILED === $this->status || self::STATUS_ERRORED === $this->status;
    }

    public function isSkipped(): bool
    {
        return self::STATUS_SKIPPED === $this->status;
    }

    public function toArray(): array
    {
        $out = [
            'name' => $this->name,
            'status' => $this->status,
            'duration_ms' => $this->durationMs,
        ];
        if (null !== $this->message) {
            $out['message'] = $this->message;
        }
        if ($this->throwable) {
            $out['file'] = $this->throwable->getFile();
            $out['line'] = $this->throwable->getLine();
        }
        return $out;
    }
}
