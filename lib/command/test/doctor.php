<?php

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies preconditions for running the YForm test suite (DB connection,
 * required tables, writable directories, PHP version).
 *
 * @package redaxo\yform
 *
 * @internal
 */
class rex_command_yform_test_doctor extends rex_console_command
{
    protected function configure(): void
    {
        $this->setDescription('Diagnoses whether the system can run the YForm test suite');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getStyle($input, $output);

        $checks = [];
        $allGreen = true;

        // 1. PHP version
        $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
        $checks[] = ['PHP >= 8.1', PHP_VERSION, $phpOk ? 'OK' : 'FAIL'];
        $allGreen = $allGreen && $phpOk;

        // 2. DB reachable
        $dbOk = false;
        $dbInfo = '';
        try {
            $sql = rex_sql::factory();
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
            $full = rex::getTable($shortName);
            $present = false;
            try {
                $present = (bool) rex_sql_table::get($full)->exists();
            } catch (Throwable) {
            }
            $checks[] = ['Table ' . $full, $present ? 'present' : 'missing', $present ? 'OK' : 'FAIL'];
            $allGreen = $allGreen && $present;
        }

        // 4. tests/fixtures dir present (optional)
        $fixturesDir = rex_path::addon('yform', 'tests/fixtures');
        $fixturesOk = is_dir($fixturesDir);
        $checks[] = ['Fixture dir exists', $fixturesDir, $fixturesOk ? 'OK' : 'INFO'];

        // 5. Transactions supported (yform install.php enforces this).
        $txOk = false;
        try {
            rex_sql::factory()->transactional(static function () {
                rex_sql::factory()->setQuery('SELECT 1');
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
            $keys = Redaxo\YForm\Test\SuiteRegistry::keys();
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
