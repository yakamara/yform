<?php

use Redaxo\YForm\Test\OutputFormatter;
use Redaxo\YForm\Test\TestRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs the "validators" test suite.
 *
 * @package redaxo\yform
 *
 * @internal
 */
class rex_command_yform_test_validators extends rex_console_command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Runs the YForm validator test suite')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter test methods by substring')
            ->addOption('bail', null, InputOption::VALUE_NONE, 'Stop on first failure')
            ->addOption('keep-fixtures', null, InputOption::VALUE_NONE, 'Do not drop test tables after run')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Custom table prefix', null)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Machine-readable JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getStyle($input, $output);
        $verbose = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;

        $runner = new TestRunner();
        $result = $runner->run('validators', [
            'filter' => (string) ($input->getOption('filter') ?: ''),
            'bail' => (bool) $input->getOption('bail'),
            'keep_fixtures' => (bool) $input->getOption('keep-fixtures'),
            'prefix' => $input->getOption('prefix'),
        ]);

        if ($input->getOption('json')) {
            $output->writeln((string) json_encode(
                $result->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ));
            return $result->isSuccess() ? 0 : 1;
        }

        (new OutputFormatter($io))->render($result, $verbose);

        return $result->isSuccess() ? 0 : 1;
    }
}
