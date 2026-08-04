<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test;

use Symfony\Component\Console\Style\SymfonyStyle;

use function strlen;

/**
 * Renders SuiteResult / TestResult for the console.
 *
 * @package redaxo\yform
 * @internal
 */
final class OutputFormatter
{
    public function __construct(
        private readonly SymfonyStyle $io,
    ) {}

    /**
     * Detailed per-test output for a single suite.
     */
    public function render(SuiteResult $result, bool $verbose = false): void
    {
        $this->io->section(sprintf('Suite: %s  (%s)', $result->key, $result->class));

        foreach ($result->results as $r) {
            $line = sprintf('%s %s', $this->statusIcon($r->status), $r->name);
            if ($r->durationMs > 0) {
                $line .= sprintf('  <fg=gray>[%dms]</>', $r->durationMs);
            }
            $this->io->writeln($line);

            if ($r->isFailedOrErrored()) {
                $msg = $r->message ?: '(no message)';
                foreach (explode("\n", $msg) as $msgLine) {
                    $this->io->writeln('    <fg=red>' . $msgLine . '</>');
                }
                if ($r->throwable) {
                    $this->io->writeln(sprintf(
                        '    <fg=gray>at %s:%d</>',
                        $this->shortenPath($r->throwable->getFile()),
                        $r->throwable->getLine(),
                    ));
                    if ($verbose) {
                        foreach (explode("\n", $r->throwable->getTraceAsString()) as $traceLine) {
                            $this->io->writeln('    <fg=gray>' . $traceLine . '</>');
                        }
                    }
                }
            } elseif ($r->isSkipped() && $r->message) {
                $this->io->writeln('    <fg=yellow>' . $r->message . '</>');
            }
        }

        $this->renderSummary($result);
    }

    /**
     * Compact one-line-per-result for the umbrella command.
     */
    public function renderCompact(SuiteResult $result): void
    {
        $line = sprintf(
            '  %s  %s  passed=%d  failed=%d  skipped=%d  %dms',
            $result->isSuccess() ? '<fg=green>OK</>' : '<fg=red>FAIL</>',
            str_pad($result->key, 24),
            $result->countPassed(),
            $result->countFailed(),
            $result->countSkipped(),
            $result->durationMs,
        );
        $this->io->writeln($line);

        if (!$result->isSuccess()) {
            foreach ($result->getFailures() as $r) {
                $this->io->writeln(sprintf('      <fg=red>✗ %s</>: %s', $r->name, $r->message ?: ''));
            }
        }
    }

    private function renderSummary(SuiteResult $result): void
    {
        $this->io->writeln('');
        $summary = sprintf(
            'Tests: %d  Passed: %d  Failed: %d  Skipped: %d  Time: %dms',
            $result->countTotal(),
            $result->countPassed(),
            $result->countFailed(),
            $result->countSkipped(),
            $result->durationMs,
        );
        if ($result->isSuccess()) {
            $this->io->success($summary);
        } else {
            $this->io->error($summary);
        }
        if ($result->leftBehindFixtures && !$result->isSuccess()) {
            $this->io->note('Left behind fixtures (--keep-fixtures or crash): ' . implode(', ', $result->leftBehindFixtures));
        }
    }

    private function statusIcon(string $status): string
    {
        return match ($status) {
            TestResult::STATUS_PASSED => '<fg=green>✓</>',
            TestResult::STATUS_FAILED => '<fg=red>✗</>',
            TestResult::STATUS_ERRORED => '<fg=red>!</>',
            TestResult::STATUS_SKIPPED => '<fg=yellow>⏵</>',
            default => '?',
        };
    }

    private function shortenPath(string $path): string
    {
        $marker = '/src/addons/';
        $pos = strpos($path, $marker);
        if (false === $pos) {
            return $path;
        }
        return '.../' . substr($path, $pos + strlen($marker));
    }
}
