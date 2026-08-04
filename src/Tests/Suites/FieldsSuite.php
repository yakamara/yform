<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Exception;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Test\AbstractTestSuite;

use function is_array;

/**
 * Tests for Field (one row -> one field wrapper).
 * Covers §3.U.2 from .claude/plans/02-test-strategy.md.
 *
 * Note: many tests instantiate Field directly via its
 * (public) constructor with synthetic input arrays, since that exercises the
 * field-wrapper independently of the table cache machinery.
 *
 * @package redaxo\yform
 * @internal
 */
final class FieldsSuite extends AbstractTestSuite
{
    public function testConstructWithValidValueDefinition(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'title',
            'label' => 'Titel',
            'prio' => 10,
            'db_type' => 'varchar(191)',
            'list_hidden' => 0,
            'search' => 1,
        ]);

        $this->assertSame('value', $field->getType());
        $this->assertSame('text', $field->getTypeName());
        $this->assertSame('title', $field->getName());
        $this->assertSame('Titel', $field->getLabel());
    }

    public function testConstructWithUnknownTypeNameThrows(): void
    {
        $this->assertThrows(
            Exception::class,
            static fn () => new Field([
                'type_id' => 'value',
                'type_name' => 'this_field_type_does_not_exist_' . uniqid(),
                'name' => 'x',
                'label' => 'X',
            ]),
        );
    }

    public function testGetElementReturnsNamedValueOrNull(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'foo',
            'label' => 'Foo',
            'default' => 'bar',
        ]);

        $this->assertSame('foo', $field->getElement('name'));
        $this->assertSame('Foo', $field->getElement('label'));
        $this->assertSame('bar', $field->getElement('default'));
        $this->assertNull($field->getElement('absolutely_unknown_key'));
    }

    public function testGetDatabaseFieldTypeUsesExplicitWhenSet(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'foo',
            'label' => 'Foo',
            'db_type' => 'varchar(80)',
        ]);

        $this->assertSame('varchar(80)', $field->getDatabaseFieldType());
    }

    public function testGetDatabaseFieldTypeFallsBackToDefaultWhenEmpty(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'foo',
            'label' => 'Foo',
            'db_type' => '',
        ]);

        $default = $field->getDatabaseFieldDefaultType();
        $this->assertSame($default, $field->getDatabaseFieldType());
    }

    public function testGetRelationTableNamesForBeManagerRelation(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'be_manager_relation',
            'name' => 'role_id',
            'label' => 'Role',
            'table' => 'rex_some_target',
            'field' => 'name',
            'type' => 0,
            'relation_table' => '',
        ]);

        $names = $field->getRelationTableNames();
        $this->assertCount(1, $names);
        $this->assertSame('rex_some_target', $names[0]);
    }

    public function testGetRelationTableNamesIncludesJunctionTable(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'be_manager_relation',
            'name' => 'tags',
            'label' => 'Tags',
            'table' => 'rex_tags',
            'field' => 'name',
            'type' => 1,
            'relation_table' => 'rex_post_tags',
        ]);

        $names = $field->getRelationTableNames();
        $this->assertCount(2, $names);
        $this->assertSame('rex_tags', $names[0]);
        $this->assertSame('rex_post_tags', $names[1]);
    }

    public function testGetRelationTableNamesEmptyForNonRelation(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'plain',
            'label' => 'Plain',
        ]);

        $this->assertCount(0, $field->getRelationTableNames());
    }

    public function testIsSearchableReflectsFlag(): void
    {
        $on = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 's_on',
            'label' => 'On',
            'search' => 1,
        ]);
        $off = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 's_off',
            'label' => 'Off',
            'search' => 0,
        ]);

        $this->assertTrue($on->isSearchable());
        $this->assertFalse($off->isSearchable());
    }

    public function testIsHiddenInListReflectsFlag(): void
    {
        $hidden = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'h1',
            'label' => 'H',
            'list_hidden' => 1,
        ]);
        $shown = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'h2',
            'label' => 'S',
            'list_hidden' => 0,
        ]);

        $this->assertTrue($hidden->isHiddenInList());
        // Note: Field::isHiddenInList() returns true even when
        // list_hidden=0 if the type's definition forbids list display. For plain
        // text fields this returns false.
        $this->assertFalse($shown->isHiddenInList());
    }

    public function testGetHooksReturnsArray(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'hk',
            'label' => 'HK',
        ]);

        // Default fields without explicit hooks return an empty array.
        $hooks = $field->getHooks();
        $this->assertTrue(is_array($hooks));
    }

    public function testToArrayReturnsFilteredValues(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'arr',
            'label' => 'Arr',
            'prio' => 5,
            'db_type' => 'text',
            'list_hidden' => 0,
            'search' => 1,
            'default' => 'hello',
        ]);

        $arr = $field->toArray();
        $this->assertArrayHasKey('type_id', $arr);
        $this->assertArrayHasKey('type_name', $arr);
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('label', $arr);
        $this->assertArrayHasKey('db_type', $arr);
    }

    public function testArrayAccessReadsAndWrites(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'aa',
            'label' => 'AA',
        ]);

        $this->assertTrue(isset($field['name']));
        $this->assertSame('aa', $field['name']);

        $field['label'] = 'Updated';
        $this->assertSame('Updated', $field['label']);

        unset($field['label']);
        $this->assertFalse(isset($field['label']));
    }

    public function testToStringReturnsName(): void
    {
        $field = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'stringable',
            'label' => 'S',
        ]);

        $this->assertSame('stringable', (string) $field);
    }

    public function testGetTypeReturnsCategoryOrFalse(): void
    {
        $value = new Field([
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'v',
            'label' => 'V',
        ]);
        $validate = new Field([
            'type_id' => 'validate',
            'type_name' => 'empty',
            'name' => 'v',
            'message' => 'm',
        ]);

        $this->assertSame('value', $value->getType());
        $this->assertSame('validate', $validate->getType());
    }
}
