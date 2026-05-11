<?php

declare(strict_types=1);

namespace Redaxo\YForm\Test;

use function count;

/**
 * Aggregated outcome of one test suite run.
 *
 * @package redaxo\yform
 * @internal
 */
final class SuiteResult
{
    /**
     * @param list<TestResult> $results
     * @param list<string>     $leftBehindFixtures
     */
    public function __construct(
        public readonly string $key,
        public readonly string $class,
        public readonly array $results,
        public readonly string $prefix,
        public readonly array $leftBehindFixtures,
        public readonly int $durationMs,
    ) {}

    public function isSuccess(): bool
    {
        foreach ($this->results as $r) {
            if ($r->isFailedOrErrored()) {
                return false;
            }
        }
        return true;
    }

    public function countTotal(): int
    {
        return count($this->results);
    }

    public function countPassed(): int
    {
        return $this->countByStatus(TestResult::STATUS_PASSED);
    }

    public function countFailed(): int
    {
        return $this->countByStatus(TestResult::STATUS_FAILED)
            + $this->countByStatus(TestResult::STATUS_ERRORED);
    }

    public function countSkipped(): int
    {
        return $this->countByStatus(TestResult::STATUS_SKIPPED);
    }

    private function countByStatus(string $status): int
    {
        $count = 0;
        foreach ($this->results as $r) {
            if ($r->status === $status) {
                ++$count;
            }
        }
        return $count;
    }

    public function getFailures(): array
    {
        return array_values(array_filter($this->results, static fn (TestResult $r) => $r->isFailedOrErrored()));
    }

    public function formatFailures(): string
    {
        $lines = [];
        foreach ($this->getFailures() as $r) {
            $lines[] = '  - ' . $r->name . ': ' . ($r->message ?: '');
            if ($r->throwable) {
                $lines[] = '    at ' . $r->throwable->getFile() . ':' . $r->throwable->getLine();
            }
        }
        return implode("\n", $lines);
    }

    public function toArray(): array
    {
        return [
            'suite' => $this->key,
            'class' => $this->class,
            'prefix' => $this->prefix,
            'left_behind_fixtures' => $this->leftBehindFixtures,
            'summary' => [
                'total' => $this->countTotal(),
                'passed' => $this->countPassed(),
                'failed' => $this->countFailed(),
                'skipped' => $this->countSkipped(),
                'duration_ms' => $this->durationMs,
                'success' => $this->isSuccess(),
            ],
            'results' => array_map(static fn (TestResult $r) => $r->toArray(), $this->results),
        ];
    }
}
