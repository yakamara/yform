<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Database\Sql;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\FieldRegistry;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\AbstractTestSuite;
use Yakamara\YForm\YForm;

use function in_array;

/**
 * Data-list search: the per-type getSearchFilter()/getSearchField() implementations.
 *
 * Two things REDAXO 6 changed make this worth its own suite:
 *   - yform's generated columns are nullable now, so "(empty)" has to match NULL,
 *     which `where($col, null)` (= `= NULL`) never did.
 *   - `number` sits on a DECIMAL column but used to borrow `integer`'s filter,
 *     which casts every operand with (int).
 *
 * @package redaxo\yform
 * @internal
 */
final class SearchSuite extends AbstractTestSuite
{
    private string $table = '';

    public function setUpBeforeClass(): void
    {
        $this->table = $this->fixtures->reserveTableName('search');
        $this->trackFixture($this->table);

        try {
            Api::removeTable($this->table);
        } catch (Throwable) {
        }
        Sql::factory()->setQuery('DROP TABLE IF EXISTS `' . $this->table . '`');

        Api::setTable([
            'table_name' => $this->table,
            'name' => 'Search fixture',
            'status' => 1,
            'hidden' => 1,
            'search' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'Titel', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'amount', 'label' => 'Menge', 'prio' => 2],
            ['type_id' => 'value', 'type_name' => 'number', 'name' => 'price', 'label' => 'Preis',
                'precision' => 10, 'scale' => 2, 'prio' => 3],
            ['type_id' => 'value', 'type_name' => 'choice', 'name' => 'color', 'label' => 'Farbe',
                'choices' => '{"Rot":"rot","Gruen":"gruen"}', 'prio' => 4],
        ]);
        Table::deleteCache();
        Api::generateTableAndFields(Table::get($this->table));
        Table::deleteCache();
    }

    public function setUp(): void
    {
        Sql::factory()->setQuery('DELETE FROM `' . $this->table . '`');

        // Two filled rows, one with '' and one with NULL in every optional column —
        // the two shapes "(empty)" has to cover.
        Sql::factory()->setQuery(
            'INSERT INTO `' . $this->table . '` (title, amount, price, color) VALUES
             ("Erster Artikel", 10, 19.99, "rot"),
             ("Zweiter Artikel", -5, 0.01, "gruen"),
             ("", NULL, NULL, ""),
             (NULL, NULL, NULL, NULL)',
        );
        Table::deleteCache();
    }

    /** Runs a field's search filter and returns the number of matching rows. */
    private function hits(string $fieldName, string $value): int
    {
        $field = Table::get($this->table)->getValueField($fieldName);
        $class = FieldRegistry::getClass('value', $field->getTypeName());

        // The data list always aliases the main table `t0`.
        $query = Dataset::query($this->table)->alias('t0');
        $query = $class::getSearchFilter([
            'value' => $value,
            'field' => $field,
            'query' => $query,
            'params' => ['field' => $field->toArray(), 'fields' => Table::get($this->table)->getFields()],
        ]);

        return $query->find()->count();
    }

    // ---------- text ----------

    public function testTextSearchMatchesExactValue(): void
    {
        $this->assertSame(1, $this->hits('title', 'Erster Artikel'));
    }

    /**
     * Text search is exact, not a LIKE — same as REDAXO 5. Pinned so the
     * behaviour is not "fixed" by accident.
     */
    public function testTextSearchIsExactNotSubstring(): void
    {
        $this->assertSame(0, $this->hits('title', 'Erster'));
    }

    public function testTextEmptyOperatorMatchesBlankAndNull(): void
    {
        $this->assertSame(2, $this->hits('title', '(empty)'));
    }

    public function testTextNotEmptyOperatorMatchesOnlyFilled(): void
    {
        $this->assertSame(2, $this->hits('title', '!(empty)'));
    }

    // ---------- integer ----------

    public function testIntegerSearchMatchesValue(): void
    {
        $this->assertSame(1, $this->hits('amount', '10'));
        $this->assertSame(1, $this->hits('amount', '-5'));
    }

    public function testIntegerSearchSupportsComparator(): void
    {
        $this->assertSame(1, $this->hits('amount', '>0'));
        $this->assertSame(1, $this->hits('amount', '<0'));
    }

    public function testIntegerSearchSupportsRange(): void
    {
        $this->assertSame(2, $this->hits('amount', '-10..20'));
    }

    public function testIntegerSearchSupportsCommaList(): void
    {
        $this->assertSame(2, $this->hits('amount', '10,-5'));
    }

    public function testIntegerEmptyOperatorMatchesNull(): void
    {
        $this->assertSame(2, $this->hits('amount', '(empty)'));
        $this->assertSame(2, $this->hits('amount', '!(empty)'));
    }

    // ---------- number (DECIMAL) ----------

    public function testNumberSearchKeepsDecimals(): void
    {
        $this->assertSame(1, $this->hits('price', '19.99'));
        // ... and the truncated integer must not match anything.
        $this->assertSame(0, $this->hits('price', '19'));
    }

    public function testNumberSearchAcceptsGermanDecimalComma(): void
    {
        $this->assertSame(1, $this->hits('price', '19,99'));
    }

    public function testNumberSearchSupportsComparatorAndRange(): void
    {
        $this->assertSame(1, $this->hits('price', '>10'));
        $this->assertSame(1, $this->hits('price', '<=1'));
        $this->assertSame(2, $this->hits('price', '0..20'));
    }

    /**
     * "19,99" is one German decimal, "0.01,19.99" is a list of two — the dot
     * disambiguates.
     */
    public function testNumberSearchTellsCommaListFromDecimalComma(): void
    {
        $this->assertSame(2, $this->hits('price', '0.01,19.99'));
        $this->assertSame(1, $this->hits('price', '19,99'));
    }

    public function testNumberEmptyOperatorMatchesNull(): void
    {
        $this->assertSame(2, $this->hits('price', '(empty)'));
        $this->assertSame(2, $this->hits('price', '!(empty)'));
    }

    public function testNumberSearchIgnoresJunk(): void
    {
        $this->assertSame(0, $this->hits('price', 'abc'));
    }

    // ---------- choice ----------

    public function testChoiceSearchMatchesValue(): void
    {
        $this->assertSame(1, $this->hits('color', 'rot'));
    }

    public function testChoiceEmptyOperatorMatchesBlankAndNull(): void
    {
        $this->assertSame(2, $this->hits('color', '(empty)'));
        $this->assertSame(2, $this->hits('color', '!(empty)'));
    }

    // ---------- search form ----------

    /**
     * Every searchable type must be able to render its search input; a type that
     * throws here takes the whole search panel down.
     */
    public function testEverySearchableTypeRendersItsSearchField(): void
    {
        $table = Table::get($this->table);

        foreach ($table->getFields() as $field) {
            if ('value' !== $field->getType()) {
                continue;
            }
            $class = FieldRegistry::getClass('value', $field->getTypeName());
            if (null === $class || !method_exists($class, 'getSearchField')) {
                continue;
            }

            $searchForm = new YForm();
            $searchForm->setObjectparams('form_name', 'searchsuite_' . $field->getName());
            $searchForm->setObjectparams('csrf_protection', false);
            $searchForm->setObjectparams('form_exit', false);

            $class::getSearchField([
                'searchForm' => $searchForm,
                'field' => $field,
                'table' => $table,
                'params' => ['field' => $field->toArray(), 'fields' => $table->getFields()],
            ]);

            $html = $searchForm->getForm();
            $this->assertTrue(
                strlen($html) > 0,
                sprintf('search field for %s (%s) rendered nothing', $field->getName(), $field->getTypeName()),
            );
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
