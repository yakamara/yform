<?php

namespace Yakamara\YForm\Manager\Table;

use Exception;
use PHPUnit\Framework\TestCase;
use rex_sql;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Field;

use function count;

/**
 * @internal
 */
class ApiTest extends TestCase
{
    public static function setUpTable($tableName)
    {
        $table = Api::setTable(
            [
                'table_name' => $tableName,
                'name' => 'Name of Table - ' . $tableName,
                'description' => 'Description of Table - ' . $tableName,
                'status' => 1,
                'list_amount' => 10,
                'list_sortfield' => 'id',
                'list_sortorder' => 'asc',
                'prio' => 9999,
                'hidden' => 1,
                'export' => 1,
                'import' => 1,
                'schema_overwrite' => 1,
            ],
        );
        return $table;
    }

    public static function setUpTableField(Table $table, $field)
    {
        $field['type_id'] ??= 'value';
        $field['type_name'] ??= 'text';
        $field['name'] ??= 'default';
        $field['label'] ??= 'Titel:';
        $field['list_hidden'] ??= 0;
        $field['search'] = $field['list_hidden'] ?? 0;
        $field['prio'] = $field['list_hidden'] ?? 99999;

        Api::setTableField($table->getTableName(), $field);
        Api::generateTableAndFields($table);
    }

    public function testTableAPI()
    {
        $prefix = 'unittest_yform_table_' . date('YmdHis') . '_';
        $tableName = $prefix . 'base';
        $tableNameCategories = $prefix . 'category';
        $tableNameRelation = $prefix . 'related';

        $table = self::setUpTable($tableName);
        self::assertEquals(
            $table::class,
            'Yakamara\YForm\Manager\Table\Table',
            'table creation failed. (\Yakamara\YForm\Manager\Table\Api::setTable)',
        );

        if ($table) {
            $fieldName = 'field_title';
            $fieldValue = 'My first Titel';

            // Text Feld erstellen
            self::setUpTableField($table, [
                'name' => $fieldName,
            ]);

            // prüfen ob es angelegt ist.

            $fields = Table::get($tableName)->getFields();
            self::assertEquals(
                count($fields),
                1,
                'field creation failed (rex_yform_manager_table_api::setTableField)',
            );

            if (1 == count($fields)) {
                self::assertEquals($fields[0]->getName(), $fieldName, 'fieldname validation failed');
            }

            // TODO: prüfen ob field gelöscht werden kann

            // YORM - Datensatz anlegen
            $dataset = Dataset::create($tableName);
            $dataset->setValue($fieldName, $fieldValue);

            self::assertTrue($dataset->save(), 'dataset creation failed (rex_yform_manager_dataset::create)');
            self::assertEquals(
                count($dataset->getMessages()),
                0,
                'dataset creation failed with Messages: ' . implode(',', $dataset->getMessages()),
            );

            if (0 == count($dataset->getMessages())) {
                $SQLDatasets = rex_sql::factory()->getArray(
                    'select * from ' . $tableName,
                );
                self::assertEquals(count($SQLDatasets), 1, 'dataset not found - creation failed (SQL Test)');

                // YORM - Datensatz auslesen
                $datasetId = $dataset->getId();
                $dataset = Dataset::get($datasetId, $tableName);
                self::assertNotNull($dataset, 'dataset not found - get via ID failed');

                // YORM - Datensatz bearbeiten
                $fieldValueEdit = $fieldValue . ' overwrite';
                $dataset
                    ->setValue($fieldName, $fieldValueEdit)
                    ->save();
                $dataset = Dataset::get($datasetId, $tableName);
                self::assertEquals(
                    $dataset->getValue($fieldName),
                    $fieldValueEdit,
                    'dataset update failes - YOrm update failed',
                );

                // YORM - Datensatz löschen
                if ($dataset) {
                    self::assertTrue($dataset->delete(), 'dataset delete failed (rex_yform_manager_dataset::delete)');

                    $dataset = Dataset::get($datasetId, $tableName);
                    self::assertFalse($dataset->exists(), 'dataset delete failed - YOrm delete failed');
                }

                // TODO - YORM - Relationen mit Relationstabelle prüfen

                // Tabelle erstellen
                // Bezugstabelle
                // Verknüpfungstabelle

                $tableCategories = self::setUpTable($tableNameCategories);
                $tableRelation = self::setUpTable($tableNameRelation);

                // Categories get an name
                self::setUpTableField($tableCategories, [
                    'name' => $fieldName,
                ]);

                // Related Table
                self::setUpTableField($tableRelation, [
                    'type_name' => 'be_manager_relation',
                    'db_type' => 'int',
                    'name' => 'base_id',
                    'label' => 'Base Item',
                    'table' => $tableName,
                    'field' => 'id',
                    'type' => 2,
                    'empty_value' => 'is empty',
                    'empty_option' => 0,
                ]);

                self::setUpTableField($tableRelation, [
                    'type_name' => 'be_manager_relation',
                    'db_type' => 'int',
                    'name' => 'category_id',
                    'label' => 'Category:',
                    'table' => $tableNameCategories,
                    'field' => 'id',
                    'type' => 2,
                    'empty_value' => 'is empty',
                    'empty_option' => 0,
                ]);

                // Base
                self::setUpTableField($table, [
                    'type_name' => 'be_manager_relation',
                    'db_type' => 'int',
                    'name' => 'categories',
                    'label' => 'Categories:',
                    'table' => $tableNameCategories,
                    'field' => $fieldName,
                    'relation_table' => $tableNameRelation,
                    'type' => 3,
                    'empty_value' => 'is empty',
                    'empty_option' => 1,
                    'size' => 10,
                ]);

                $cat1 = Dataset::create($tableNameCategories)
                    ->setValue($fieldName, 'Category 1');
                $cat1->save();

                $cat2 = Dataset::create($tableNameCategories)
                    ->setValue($fieldName, 'Category 2');
                $cat2->save();

                $cat3 = Dataset::create($tableNameCategories)
                    ->setValue($fieldName, 'Category 3');
                $cat3->save();

                Dataset::create($tableName)
                    ->setValue($fieldName, 'Mein Neuer Wert')
                    ->save();

                Dataset::create($tableName)
                    ->setValue($fieldName, 'Mein Neuer Wert mit 2 Kategorien')
                    ->setValue('categories', [
                        $cat1->getId(),
                        $cat2->getId(),
                    ])
                    ->save();
            }
        }

        Api::removeTable($tableName);

        $table = Table::get($tableName);

        self::assertNull($table, 'table schema removing failed');

        // Cleanup
        try {
            rex_sql::factory()->setQuery(
                'DROP Table ' . $tableName,
            );
            rex_sql::factory()->setQuery(
                'DROP Table ' . $tableNameCategories,
            );
            rex_sql::factory()->setQuery(
                'DROP Table ' . $tableNameRelation,
            );
            rex_sql::factory()->setQuery(
                'delete from ' . Table::table() . ' where table_name LIKE :table_name ',
                [
                    ':table_name' => $prefix . '%',
                ],
            );
            rex_sql::factory()->setQuery(
                'delete from ' . Field::table() . ' where table_name LIKE :table_name ',
                [
                    ':table_name' => $prefix . '%',
                ],
            );

            Table::deleteCache();
        } catch (Exception $e) {
        }
    }

    public function t2estHasValue()
    {
        // Folgende Tests sind möglich

        // PHP Formular
        // .. erstellen
        // .. update
        // .. delete

        // Tabelle erstellen
        // schemaupdate
        // Tabelle export
        // Tabelle löschen
        // tabelle import

        // E-Mail Versand Template

        // $media = $this->createMediaWithoutConstructor();
        //
        // /** @psalm-suppress UndefinedPropertyAssignment */
        // $media->med_foo = 'teststring';
        //
        // static::assertTrue($media->hasValue('med_foo'));
        // static::assertTrue($media->hasValue('foo'));
        //
        // static::assertFalse($media->hasValue('bar'));
        // static::assertFalse($media->hasValue('med_bar'));
    }

    public function t2estGetValue()
    {
        // $media = $this->createMediaWithoutConstructor();
        //
        // /** @psalm-suppress UndefinedPropertyAssignment */
        // $media->med_foo = 'teststring';
        //
        // static::assertEquals('teststring', $media->getValue('med_foo'));
        // static::assertEquals('teststring', $media->getValue('foo'));
        //
        // static::assertNull($media->getValue('bar'));
        // static::assertNull($media->getValue('med_bar'));
    }

    // private function createMediaWithoutConstructor(): rex_media
    // {
    //     /** @noinspection PhpIncompatibleReturnTypeInspection */
    //     return (new ReflectionClass(rex_media::class))->newInstanceWithoutConstructor();
    // }
}
