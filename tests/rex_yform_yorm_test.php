<?php

namespace yform\tests;

use PHPUnit\Framework\TestCase;
use rex_sql;
use rex_yform_manager_dataset;
use rex_yform_manager_field;
use rex_yform_manager_table;
use rex_yform_manager_table_api;

use function count;

/**
 * @internal
 */
class rex_yform_yorm_test extends TestCase
{
    public static function setUpTable($tableName)
    {
        $table = rex_yform_manager_table_api::setTable(
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

    public static function setUpTableField(rex_yform_manager_table $table, $field)
    {
        $field['type_id'] ??= 'value';
        $field['type_name'] ??= 'text';
        $field['name'] ??= 'default';
        $field['label'] ??= 'Titel:';
        $field['list_hidden'] ??= 0;
        $field['search'] = $field['list_hidden'] ?? 0;
        $field['prio'] = $field['list_hidden'] ?? 99999;

        rex_yform_manager_table_api::setTableField($table->getTableName(), $field);
        rex_yform_manager_table_api::generateTableAndFields($table);
    }

    public function testTableAPI()
    {
        $prefix = 'unittest_yform_table_' . date('YmdHis') . '_';
        $tableName = $prefix . 'base';

        $table = self::setUpTable($tableName);
        static::assertEquals(
            $table::class,
            'rex_yform_manager_table',
            'table creation failed. (rex_yform_manager_table_api::setTable)',
        );

        if ($table) {
            $fieldName = 'field_title';
            $fieldValue = 'My first Titel';

            // Text Feld erstellen
            self::setUpTableField($table, [
                'name' => $fieldName,
            ]);

            // prüfen ob es angelegt ist.

            $fields = rex_yform_manager_table::get($tableName)->getFields();
            static::assertEquals(
                count($fields),
                1,
                'field creation failed (rex_yform_manager_table_api::setTableField)',
            );

            if (1 == count($fields)) {
                static::assertEquals($fields[0]->getName(), $fieldName, 'fieldname validation failed');
            }

            // TODO: prüfen ob field gelöscht werden kann

            // YORM - Datensatz anlegen
            $dataset = rex_yform_manager_dataset::create($tableName);
            $dataset->setValue($fieldName, $fieldValue);

            static::assertTrue($dataset->save(), 'dataset creation failed (rex_yform_manager_dataset::create)');
            static::assertEquals(
                count($dataset->getMessages()),
                0,
                'dataset creation failed with Messages: ' . implode(',', $dataset->getMessages()),
            );

            if (0 == count($dataset->getMessages())) {
                $SQLDatasets = rex_sql::factory()->getArray(
                    'select * from ' . $tableName,
                );
                static::assertEquals(count($SQLDatasets), 1, 'dataset not found - creation failed (SQL Test)');

                // YORM - Datensatz auslesen
                $datasetId = $dataset->getId();
                $dataset = rex_yform_manager_dataset::get($datasetId, $tableName);
                static::assertNotNull($dataset, 'dataset not found - get via ID failed');

                // YORM - Datensatz bearbeiten
                $fieldValueEdit = $fieldValue . ' overwrite';
                $dataset
                    ->setValue($fieldName, $fieldValueEdit)
                    ->save();
                $dataset = rex_yform_manager_dataset::get($datasetId, $tableName);
                static::assertEquals(
                    $dataset->getValue($fieldName),
                    $fieldValueEdit,
                    'dataset update failes - YOrm update failed',
                );

                // YORM - Datensatz löschen
                if ($dataset) {
                    static::assertTrue($dataset->delete(), 'dataset delete failed (rex_yform_manager_dataset::delete)');

                    $dataset = rex_yform_manager_dataset::get($datasetId, $tableName);
                    static::assertFalse($dataset->exists(), 'dataset delete failed - YOrm delete failed');
                }

                // TODO - YORM - Relationen mit Relationstabelle prüfen

                // Tabelle erstellen
                // Bezugstabelle
                // Verknüpfungstabelle

                $tableNameCategories = $prefix . 'category';
                $tableNameRelation = $prefix . 'related';

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

                $cat1 = rex_yform_manager_dataset::create($tableNameCategories)
                    ->setValue($fieldName, 'Category 1');
                $cat1->save();

                $cat2 = rex_yform_manager_dataset::create($tableNameCategories)
                    ->setValue($fieldName, 'Category 2');
                $cat2->save();

                $cat3 = rex_yform_manager_dataset::create($tableNameCategories)
                    ->setValue($fieldName, 'Category 3');
                $cat3->save();

                rex_yform_manager_dataset::create($tableName)
                    ->setValue($fieldName, 'Mein Neuer Wert')
                    ->save();

                rex_yform_manager_dataset::create($tableName)
                    ->setValue($fieldName, 'Mein Neuer Wert mit 2 Kategorien')
                    ->setValue('categories', [
                        $cat1->getId(),
                        $cat2->getId(),
                    ])
                    ->save();
            }
        }

        rex_yform_manager_table_api::removeTable($tableName);

        $table = rex_yform_manager_table::get($tableName);

        static::assertNull($table, 'table schema removing failed');

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
                'delete from ' . rex_yform_manager_table::table() . ' where table_name LIKE :table_name ',
                [
                    ':table_name' => $prefix . '%',
                ],
            );
            rex_sql::factory()->setQuery(
                'delete from ' . rex_yform_manager_field::table() . ' where table_name LIKE :table_name ',
                [
                    ':table_name' => $prefix . '%',
                ],
            );

            rex_yform_manager_table::deleteCache();
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
