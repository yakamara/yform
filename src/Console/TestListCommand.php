<?php

namespace Yakamara\YForm\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Redaxo\Core\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yakamara\YForm\Test\SuiteRegistry;

/**
 * Lists all registered YForm test suites.
 *
 * @package redaxo\yform
 *
 * @internal
 */
#[AsCommand(name: 'yform:test:list')]
class TestListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setDescription('Lists all registered YForm test suites');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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
        $io->writeln('Run all with: <info>php redaxo/bin/console yform:test</info>');
        $io->writeln('Run one with: <info>php redaxo/bin/console yform:test:<key></info>');

        return 0;
    }
}
