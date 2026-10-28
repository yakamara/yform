<?php

namespace Yakamara\YForm\Console;

use Redaxo\Core\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yakamara\YForm\Schema\SchemaSync;
use Yakamara\YForm\Table\Tables;

use function implode;
use function in_array;
use function sprintf;

/**
 * Shows and applies the differences between table definitions and database.
 *
 * `console migrate` applies everything except column drops automatically; this command is for inspection and
 * for dropping columns that no field uses anymore.
 */
#[AsCommand(name: 'yform:schema', description: 'Shows or applies the differences between the YForm table definitions and the database')]
final class SchemaCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply the changes')
            ->addOption('drop', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Drop an obsolete column, as table.column (with --apply)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        /** @var list<string> $drop */
        $drop = $input->getOption('drop');

        $rows = [];
        $pending = false;
        foreach (Tables::all() as $table) {
            $plan = SchemaSync::plan($table);
            $pending = $pending || $plan->hasChanges();

            $changes = [];
            if ($plan->create) {
                $changes[] = 'create table';
            }
            if ([] !== $plan->add) {
                $changes[] = 'add: ' . implode(', ', $plan->add);
            }
            if ([] !== $plan->modify) {
                $changes[] = 'modify: ' . implode(', ', $plan->modify);
            }
            if ([] !== $plan->indexes) {
                $changes[] = 'indexes: ' . implode(', ', $plan->indexes);
            }
            if ([] !== $plan->additional) {
                $changes[] = 'tables: ' . implode(', ', $plan->additional);
            }
            if ([] !== $plan->obsolete) {
                $changes[] = '<comment>obsolete: ' . implode(', ', $plan->obsolete) . '</comment>';
            }
            $rows[] = [$table->name, $table::class, [] === $changes ? '<info>up to date</info>' : implode("\n", $changes)];

            if ($apply) {
                $tableDrops = [];
                foreach ($drop as $item) {
                    [$tableName, $column] = explode('.', $item, 2) + [1 => ''];
                    if ($tableName === $table->name && in_array($column, $plan->obsolete, true)) {
                        $tableDrops[] = $column;
                    }
                }
                SchemaSync::apply($table, $tableDrops);
                foreach ($tableDrops as $column) {
                    $io->warning(sprintf('Dropped %s.%s', $table->name, $column));
                }
            }
        }

        $io->table(['Table', 'Definition', 'Changes'], $rows);

        if ($apply) {
            $io->success('Schema applied.');
        } elseif ($pending) {
            $io->note('Run with --apply to apply the changes (obsolete columns are only dropped with --drop=table.column).');
        }

        return 0;
    }
}
