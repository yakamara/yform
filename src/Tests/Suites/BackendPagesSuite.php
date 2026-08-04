<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Backend\Controller;
use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Security\User;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Manager;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\AbstractTestSuite;

use function in_array;

/**
 * Renders the Table Manager's data pages the same way pages/manager.data_edit.php does.
 *
 * This closes a real gap: everything else in the suite drives the ORM or a YForm
 * object directly, so a broken *page* — a fragment that still references an R5 class,
 * a template that no longer resolves — went unnoticed until someone opened the
 * backend. Checking it over HTTP is no substitute: an expired session answers 200
 * with a login page, and the opcode cache can serve a stale file, so both look
 * healthy while the code on disk is broken.
 *
 * Every test asserts on the rendered markup, not just on "no exception": Manager
 * catches Exceptions internally and turns them into a warning box, so a page can
 * fail while still returning a 200-ish string.
 *
 * @package redaxo\yform
 * @internal
 */
final class BackendPagesSuite extends AbstractTestSuite
{
    private string $table = '';
    private int $firstId = 0;

    public function setUpBeforeClass(): void
    {
        $this->table = $this->fixtures->reserveTableName('bepage');
        $this->trackFixture($this->table);

        try {
            Api::removeTable($this->table);
        } catch (Throwable) {
        }
        Sql::factory()->setQuery('DROP TABLE IF EXISTS `' . $this->table . '`');

        Api::setTable([
            'table_name' => $this->table,
            'name' => 'Backend page fixture',
            'status' => 1,
            'hidden' => 1,
            'search' => 1,
            'export' => 1,
            'import' => 1,
            'history' => 1,
            'mass_deletion' => 1,
            'mass_edit' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'Titel', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'textarea', 'name' => 'body', 'label' => 'Text', 'prio' => 2],
            ['type_id' => 'value', 'type_name' => 'choice', 'name' => 'color', 'label' => 'Farbe',
                'choices' => '{"Rot":"rot","Blau":"blau"}', 'prio' => 3],
            ['type_id' => 'value', 'type_name' => 'checkbox', 'name' => 'online', 'label' => 'Online', 'prio' => 4],
            ['type_id' => 'value', 'type_name' => 'datestamp', 'name' => 'createdate', 'label' => 'Erstellt',
                'format' => 'Y-m-d H:i:s', 'only_empty' => 1, 'prio' => 5],
        ]);
        Table::deleteCache();
        Api::generateTableAndFields(Table::get($this->table));
        Table::deleteCache();
    }

    public function setUp(): void
    {
        Sql::factory()->setQuery('DELETE FROM `' . $this->table . '`');

        foreach ([['Erster Eintrag', 'rot'], ['Zweiter Eintrag', 'blau']] as [$title, $color]) {
            $ds = Dataset::create($this->table);
            $ds->setValue('title', $title);
            $ds->setValue('body', 'Inhalt zu ' . $title);
            $ds->setValue('color', $color);
            $ds->setValue('online', 1);
            $ds->save();
        }

        $this->firstId = (int) Sql::factory()->getArray('SELECT MIN(id) AS m FROM `' . $this->table . '`')[0]['m'];
        Table::deleteCache();
    }

    /**
     * Renders a data page for the given request parameters, exactly like
     * pages/manager.data_edit.php — but without swallowing failures.
     *
     * @param array<string, mixed> $params
     */
    private function renderDataPage(array $params = []): string
    {
        $keys = ['table_name', 'func', 'data_id', 'rex_yform_manager_popup', 'rex_yform_filter', 'rex_yform_set', 'search', 'list'];
        $previous = [];
        foreach ($keys as $key) {
            $previous[$key] = [$_GET[$key] ?? null, $_REQUEST[$key] ?? null];
            unset($_GET[$key], $_REQUEST[$key]);
        }

        $_GET['table_name'] = $_REQUEST['table_name'] = $this->table;
        foreach ($params as $key => $value) {
            $_GET[$key] = $value;
            $_REQUEST[$key] = $value;
        }

        $user = Core::getUser();
        if (null === $user) {
            Core::setProperty('user', User::require(1));
        }

        // The manager asks the controller which page it is on; under the console no
        // backend page has been set, and getCurrentPage() throws rather than guessing.
        Controller::setCurrentPage('yform/manager/data_edit');

        // The relation and upload widgets echo part of their markup.
        ob_start();
        try {
            $manager = new Manager();
            $manager->setTable(Table::require($this->table));
            $manager->setLinkVars(['page' => 'yform/manager/data_edit', 'table_name' => $this->table]);

            $html = $manager->getDataPage();
            $echoed = (string) ob_get_clean();

            return $echoed . $html;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        } finally {
            if (null === $user) {
                Core::setProperty('user', null);
            }
            foreach ($keys as $key) {
                [$get, $request] = $previous[$key];

                if (null === $get) {
                    unset($_GET[$key]);
                } else {
                    $_GET[$key] = $get;
                }

                if (null === $request) {
                    unset($_REQUEST[$key]);
                } else {
                    $_REQUEST[$key] = $request;
                }
            }
        }
    }

    /**
     * A page that "renders" but only contains yform's own warning box is broken —
     * Manager catches Exceptions and turns them into one.
     */
    private function assertRendersWithoutWarningBox(string $html, string $what): void
    {
        $this->assertTrue(strlen($html) > 200, $what . ': output is suspiciously short (' . strlen($html) . ' bytes)');

        foreach (['Fatal error', 'not found', 'Uncaught', 'SQLSTATE', 'Stacktrace'] as $marker) {
            $this->assertFalse(
                str_contains($html, $marker),
                sprintf('%s: rendered output contains "%s"', $what, $marker),
            );
        }
    }

    // ---------- the plain data list ----------

    /**
     * The page from the bug report: opening a table's data list. It broke because a
     * manager fragment still referenced the R5 class rex_yform_list, and nothing in
     * the suite rendered that fragment.
     */
    public function testDataListRenders(): void
    {
        $html = $this->renderDataPage();

        $this->assertRendersWithoutWarningBox($html, 'data list');
        $this->assertStringContains('Erster Eintrag', $html, 'the list must show its rows');
        $this->assertStringContains('Zweiter Eintrag', $html);
        $this->assertStringContains('Titel', $html, 'the list must show its column labels');
    }

    public function testDataListShowsTheActionColumn(): void
    {
        $html = $this->renderDataPage();

        // edit / delete links carry the data_id of the row
        $this->assertStringContains('data_id=' . $this->firstId, $html);
        $this->assertStringContains('func=edit', $html);
    }

    public function testDataListSortedRenders(): void
    {
        $html = $this->renderDataPage(['sort' => 'title', 'sorttype' => 'desc']);

        $this->assertRendersWithoutWarningBox($html, 'sorted list');
        $this->assertStringContains('Zweiter Eintrag', $html);
    }

    // ---------- add / edit forms ----------

    public function testAddFormRenders(): void
    {
        $html = $this->renderDataPage(['func' => 'add']);

        $this->assertRendersWithoutWarningBox($html, 'add form');
        $this->assertStringContains('<form', $html);
        foreach (['Titel', 'Text', 'Farbe', 'Online'] as $label) {
            $this->assertStringContains($label, $html, 'the add form must show the ' . $label . ' field');
        }
    }

    public function testEditFormRendersWithTheStoredValues(): void
    {
        $html = $this->renderDataPage(['func' => 'edit', 'data_id' => $this->firstId]);

        $this->assertRendersWithoutWarningBox($html, 'edit form');
        $this->assertStringContains('<form', $html);
        $this->assertStringContains('Erster Eintrag', $html, 'the edit form must be pre-filled');
    }

    // ---------- search ----------

    public function testSearchFormRenders(): void
    {
        $html = $this->renderDataPage(['search' => '1']);

        $this->assertRendersWithoutWarningBox($html, 'search form');
        $this->assertStringContains('Titel', $html);
    }

    public function testFilteredListRendersAndFilters(): void
    {
        $html = $this->renderDataPage(['rex_yform_filter' => ['color' => 'rot']]);

        $this->assertRendersWithoutWarningBox($html, 'filtered list');
        $this->assertStringContains('Erster Eintrag', $html);
        $this->assertFalse(
            str_contains($html, 'Zweiter Eintrag'),
            'the filter must exclude the blue row',
        );
    }

    // ---------- popup ----------

    public function testPopupListRenders(): void
    {
        $html = $this->renderDataPage(['rex_yform_manager_popup' => 1]);

        $this->assertRendersWithoutWarningBox($html, 'popup list');
        $this->assertStringContains('Erster Eintrag', $html);
    }

    public function testPopupAddFormRenders(): void
    {
        $html = $this->renderDataPage(['rex_yform_manager_popup' => 1, 'func' => 'add']);

        $this->assertRendersWithoutWarningBox($html, 'popup add form');
        $this->assertStringContains('<form', $html);
    }

    // ---------- import / export ----------

    public function testExportPageRenders(): void
    {
        $html = $this->renderDataPage(['func' => 'export']);
        $this->assertRendersWithoutWarningBox($html, 'export page');
    }

    public function testImportPageRenders(): void
    {
        $html = $this->renderDataPage(['func' => 'import']);
        $this->assertRendersWithoutWarningBox($html, 'import page');
    }

    // ---------- every fixture table ----------

    /**
     * Renders the list for every table registered in this installation. Catches a
     * field type whose fragment or list formatter breaks only for certain configs.
     */
    public function testEveryRegisteredTableListRenders(): void
    {
        $originalTable = $this->table;

        try {
            foreach (Table::getAll() as $table) {
                if (!$table->isActive()) {
                    continue;
                }

                $this->table = $table->getTableName();
                $html = $this->renderDataPage();
                $this->assertRendersWithoutWarningBox($html, 'list of ' . $this->table);
            }
        } finally {
            $this->table = $originalTable;
        }
    }

    private function trackFixture(string $tableName): void
    {
        $reflection = new ReflectionClass($this->fixtures);
        $prop = $reflection->getProperty('createdTables');
        $list = (array) $prop->getValue($this->fixtures);
        if (!in_array($tableName, $list, true)) {
            $list[] = $tableName;
            $prop->setValue($this->fixtures, $list);
        }
    }
}
