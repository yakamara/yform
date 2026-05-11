<?php

use Redaxo\YForm\Test\SuiteRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists all registered YForm test suites.
 *
 * @package redaxo\yform
 *
 * @internal
 */
class rex_command_yform_test_list extends rex_console_command
{
    protected function configure(): void
    {
        $this->setDescription('Lists all registered YForm test suites');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getStyle($input, $output);

        $rows = [];
        foreach (SuiteRegistry::all() as $key => $info) {
            $rows[] = [$key, $info['class'], $info['description']];
        }

        if (!$rows) {
            $io->warning('No test suites registered.');
            return 0;
        }

        $io->title('YForm Test Suites');
        $io->table(['Key', 'Class', 'Description'], $rows);
        $io->writeln(sprintf('Run all with: <info>php redaxo/bin/console yform:test</info>'));
        $io->writeln(sprintf('Run one with: <info>php redaxo/bin/console yform:test:<key></info>'));

        return 0;
    }
}
