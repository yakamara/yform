<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test;

use InvalidArgumentException;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Manager\Query;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Tests\Suites\ActionsSuite;
use Yakamara\YForm\Tests\Suites\AuthorizationSuite;
use Yakamara\YForm\Tests\Suites\BackendPagesSuite;
use Yakamara\YForm\Tests\Suites\CacheSuite;
use Yakamara\YForm\Tests\Suites\DatasetsSuite;
use Yakamara\YForm\Tests\Suites\E2ESuite;
use Yakamara\YForm\Tests\Suites\ExtensionPointsSuite;
use Yakamara\YForm\Tests\Suites\FieldsSuite;
use Yakamara\YForm\Tests\Suites\FieldTypesSuite;
use Yakamara\YForm\Tests\Suites\FragmentsSuite;
use Yakamara\YForm\Tests\Suites\QueriesSuite;
use Yakamara\YForm\Tests\Suites\RelationsSuite;
use Yakamara\YForm\Tests\Suites\SearchSuite;
use Yakamara\YForm\Tests\Suites\TableReadSuite;
use Yakamara\YForm\Tests\Suites\TablesSuite;
use Yakamara\YForm\Tests\Suites\ValidatorsSuite;

/**
 * Maps suite keys (used in console commands) to their suite classes.
 *
 * The registry is the single source of truth: console commands look up
 * which class to instantiate by suite key, and TestRunner uses it to
 * discover all available suites.
 *
 * Add new suites here as they get implemented.
 *
 * @package redaxo\yform
 * @internal
 */
final class SuiteRegistry
{
    /**
     * @return array<string, array{class: class-string<AbstractTestSuite>, description: string}>
     */
    public static function all(): array
    {
        return [
            'tables' => [
                'class' => TablesSuite::class,
                'description' => 'Api CRUD (3.U.3)',
            ],
            'table-read' => [
                'class' => TableReadSuite::class,
                'description' => 'Table read-only API (3.U.1)',
            ],
            'fields' => [
                'class' => FieldsSuite::class,
                'description' => 'Field wrapper (3.U.2)',
            ],
            'datasets' => [
                'class' => DatasetsSuite::class,
                'description' => 'Dataset CRUD (3.I.1)',
            ],
            'queries' => [
                'class' => QueriesSuite::class,
                'description' => 'Query builder (3.I.2)',
            ],
            'validators' => [
                'class' => ValidatorsSuite::class,
                'description' => 'Validator types empty/type/compare/unique/preg/etc (3.U.5)',
            ],
            'actions' => [
                'class' => ActionsSuite::class,
                'description' => 'Action types db/callback/showtext/tpl2email/etc (3.U.6)',
            ],
            'extension-points' => [
                'class' => ExtensionPointsSuite::class,
                'description' => 'YFORM_* extension points fire with correct subject/params (3.I.4)',
            ],
            'authorization' => [
                'class' => AuthorizationSuite::class,
                'description' => 'complex_perm + table-level isGranted (3.U.8)',
            ],
            'cache' => [
                'class' => CacheSuite::class,
                'description' => 'Table cache layer (3.U.7)',
            ],
            'field-types' => [
                'class' => FieldTypesSuite::class,
                'description' => 'Type-specific behavior datestamp/choice/uuid/checkbox/etc (3.U.4)',
            ],
            'backend-pages' => [
                'class' => BackendPagesSuite::class,
                'description' => 'Table Manager data pages render (list/add/edit/search/popup) (3.E2E.2)',
            ],
            'fragments' => [
                'class' => FragmentsSuite::class,
                'description' => 'Fragment layer: backend/frontend split and delegation (3.U.10)',
            ],
            'relations' => [
                'class' => RelationsSuite::class,
                'description' => 'be_manager_relation in all six modes incl. inline 1-n (3.I.5)',
            ],
            'search' => [
                'class' => SearchSuite::class,
                'description' => 'Per-type search filters and the (empty) operator (3.U.9)',
            ],
            'e2e' => [
                'class' => E2ESuite::class,
                'description' => 'End-to-end install lifecycle + multi-field form submit (3.E2E)',
            ],
            // Add further suites here as they get implemented:
            // 'tablesets'        => [...]
        ];
    }

    /**
     * @return class-string<AbstractTestSuite>
     */
    public static function get(string $key): string
    {
        $all = self::all();
        if (!isset($all[$key])) {
            throw new InvalidArgumentException(sprintf('Unknown test suite "%s". Available: %s', $key, implode(', ', array_keys($all))));
        }
        return $all[$key]['class'];
    }

    public static function getDescription(string $key): string
    {
        $all = self::all();
        return $all[$key]['description'] ?? '';
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
