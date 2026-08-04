<?php

namespace Yakamara\YForm\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Redaxo\Core\Console\Command\AbstractCommand;
use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use Redaxo\Core\Filesystem\Path;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yakamara\YForm\Test\SuiteRegistry;

/**
 * Verifies preconditions for running the YForm test suite (DB connection,
 * required tables, writable directories, PHP version).
 *
 * @package redaxo\yform
 *
 * @internal
 */
#[AsCommand(name: 'yform:test:doctor')]
class TestDoctorCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setDescription('Diagnoses whether the system can run the YForm test suite');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $checks = [];
        // 1. PHP version
        $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
        $checks[] = ['PHP >= 8.1', PHP_VERSION, $phpOk ? 'OK' : 'FAIL'];
        $allGreen = $phpOk;

        // 2. DB reachable
        $dbOk = false;
        $dbInfo = '';
        try {
            $sql = Sql::factory();
            $row = $sql->getArray('SELECT VERSION() AS v');
            $dbInfo = (string) ($row[0]['v'] ?? '?');
            $dbOk = '' !== $dbInfo;
        } catch (Throwable $e) {
            $dbInfo = $e->getMessage();
        }
        $checks[] = ['MySQL reachable', $dbInfo, $dbOk ? 'OK' : 'FAIL'];
        $allGreen = $allGreen && $dbOk;

        // 3. yform system tables present
        $sysTables = ['yform_table', 'yform_field', 'yform_email_template', 'yform_history'];
        foreach ($sysTables as $shortName) {
            $full = Core::getTable($shortName);
            $present = false;
            try {
                $present = (bool) DbTable::get($full)->exists();
            } catch (Throwable) {
            }
            $checks[] = ['Table ' . $full, $present ? 'present' : 'missing', $present ? 'OK' : 'FAIL'];
            $allGreen = $allGreen && $present;
        }

        // 4. tests/fixtures dir present (optional)
        $fixturesDir = Path::addon('yform', 'tests/fixtures');
        $fixturesOk = is_dir($fixturesDir);
        $checks[] = ['Fixture dir exists', $fixturesDir, $fixturesOk ? 'OK' : 'INFO'];

        // 5. Transactions supported (yform install.php enforces this).
        $txOk = false;
        try {
            Sql::factory()->transactional(static function () {
                Sql::factory()->setQuery('SELECT 1');
            });
            $txOk = true;
        } catch (Throwable $e) {
            // leave txOk = false
        }
        $checks[] = ['Transactions supported', $txOk ? 'yes' : 'no', $txOk ? 'OK' : 'FAIL'];
        $allGreen = $allGreen && $txOk;

        // 6. SuiteRegistry resolvable
        $registryOk = false;
        $registryInfo = '';
        try {
            $keys = SuiteRegistry::keys();
            $registryOk = is_array($keys);
            $registryInfo = (string) count($keys) . ' suite(s)';
        } catch (Throwable $e) {
            $registryInfo = $e->getMessage();
        }
        $checks[] = ['SuiteRegistry resolves', $registryInfo, $registryOk ? 'OK' : 'FAIL'];
        $allGreen = $allGreen && $registryOk;

        $io->title('YForm Test Doctor');
        $io->table(['Check', 'Detail', 'Status'], $checks);

        if ($allGreen) {
            $io->success('All preconditions met.');
            return 0;
        }

        $io->error('One or more preconditions failed. Run yform:test only after fixing the issues above.');
        return 1;
    }
}
