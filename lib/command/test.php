<?php

use Redaxo\YForm\Test\OutputFormatter;
use Redaxo\YForm\Test\SuiteRegistry;
use Redaxo\YForm\Test\SuiteResult;
use Redaxo\YForm\Test\TestRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs every registered YForm test suite (or only those passed via --suite).
 *
 * @package redaxo\yform
 *
 * @internal
 */
class rex_command_yform_test extends rex_console_command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Runs all YForm test suites')
            ->addOption('suite', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Run only these suites (comma-separated or repeated)')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter test methods by substring')
            ->addOption('bail', null, InputOption::VALUE_NONE, 'Stop on first failure')
            ->addOption('keep-fixtures', null, InputOption::VALUE_NONE, 'Do not drop test tables after run')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Custom table prefix', null)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Machine-readable JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getStyle($input, $output);

        $only = $input->getOption('suite');
        if (is_array($only) && [] === $only) {
            $only = SuiteRegistry::keys();
        } else {
            // Support comma-separated values inside a single --suite=a,b option.
            $expanded = [];
            foreach ($only as $value) {
                foreach (explode(',', (string) $value) as $part) {
                    $part = trim($part);
                    if ('' !== $part) {
                        $expanded[] = $part;
                    }
                }
            }
            $only = $expanded;
        }

        $runner = new TestRunner();
        $allResults = [];
        $exitCode = 0;
        $verbose = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;

        foreach ($only as $suiteKey) {
            try {
                $result = $runner->run($suiteKey, [
                    'filter'        => (string) ($input->getOption('filter') ?: ''),
                    'bail'          => (bool) $input->getOption('bail'),
                    'keep_fixtures' => (bool) $input->getOption('keep-fixtures'),
                    'prefix'        => $input->getOption('prefix'),
                ]);
            } catch (Throwable $e) {
                $io->error(sprintf('Suite "%s" could not run: %s', $suiteKey, $e->getMessage()));
                $exitCode = 1;
                continue;
            }

            $allResults[] = $result;

            if ($input->getOption('json')) {
                continue;
            }

            (new OutputFormatter($io))->renderCompact($result);

            if (!$result->isSuccess()) {
                $exitCode = 1;
                if ($input->getOption('bail')) {
                    break;
                }
            }
        }

        if ($input->getOption('json')) {
            $output->writeln((string) json_encode(
                array_map(static fn (SuiteResult $r) => $r->toArray(), $allResults),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ));
            return $exitCode;
        }

        $io->newLine();
        $rows = [];
        $totalPassed = $totalFailed = $totalSkipped = $totalTime = 0;
        foreach ($allResults as $r) {
            $rows[] = [
                $r->key,
                $r->countPassed(),
                $r->countFailed(),
                $r->countSkipped(),
                $r->durationMs . 'ms',
                $r->isSuccess() ? 'OK' : 'FAIL',
            ];
            $totalPassed  += $r->countPassed();
            $totalFailed  += $r->countFailed();
            $totalSkipped += $r->countSkipped();
            $totalTime    += $r->durationMs;
        }
        $io->table(['Suite', 'Passed', 'Failed', 'Skipped', 'Time', 'Status'], $rows);

        $summary = sprintf('Total: %d passed, %d failed, %d skipped in %dms', $totalPassed, $totalFailed, $totalSkipped, $totalTime);
        if (0 === $exitCode) {
            $io->success($summary);
        } else {
            $io->error($summary);
        }

        if ($verbose) {
            foreach ($allResults as $r) {
                if (!$r->isSuccess()) {
                    $io->section('Details: ' . $r->key);
                    (new OutputFormatter($io))->render($r);
                }
            }
        }

        return $exitCode;
    }
}
