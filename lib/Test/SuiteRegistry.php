<?php

declare(strict_types=1);

namespace Redaxo\YForm\Test;

use Redaxo\YForm\Tests\Suites\ActionsSuite;
use Redaxo\YForm\Tests\Suites\AuthorizationSuite;
use Redaxo\YForm\Tests\Suites\CacheSuite;
use Redaxo\YForm\Tests\Suites\DatasetsSuite;
use Redaxo\YForm\Tests\Suites\ExtensionPointsSuite;
use Redaxo\YForm\Tests\Suites\FieldsSuite;
use Redaxo\YForm\Tests\Suites\FieldTypesSuite;
use Redaxo\YForm\Tests\Suites\QueriesSuite;
use Redaxo\YForm\Tests\Suites\TableReadSuite;
use Redaxo\YForm\Tests\Suites\TablesSuite;
use Redaxo\YForm\Tests\Suites\ValidatorsSuite;

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
                'class'       => TablesSuite::class,
                'description' => 'rex_yform_manager_table_api CRUD (3.U.3)',
            ],
            'table-read' => [
                'class'       => TableReadSuite::class,
                'description' => 'rex_yform_manager_table read-only API (3.U.1)',
            ],
            'fields' => [
                'class'       => FieldsSuite::class,
                'description' => 'rex_yform_manager_field wrapper (3.U.2)',
            ],
            'datasets' => [
                'class'       => DatasetsSuite::class,
                'description' => 'rex_yform_manager_dataset CRUD (3.I.1)',
            ],
            'queries' => [
                'class'       => QueriesSuite::class,
                'description' => 'rex_yform_manager_query builder (3.I.2)',
            ],
            'validators' => [
                'class'       => ValidatorsSuite::class,
                'description' => 'Validator types empty/type/compare/unique/preg/etc (3.U.5)',
            ],
            'actions' => [
                'class'       => ActionsSuite::class,
                'description' => 'Action types db/callback/showtext/tpl2email/etc (3.U.6)',
            ],
            'extension-points' => [
                'class'       => ExtensionPointsSuite::class,
                'description' => 'YFORM_* extension points fire with correct subject/params (3.I.4)',
            ],
            'authorization' => [
                'class'       => AuthorizationSuite::class,
                'description' => 'complex_perm + table-level isGranted (3.U.8)',
            ],
            'cache' => [
                'class'       => CacheSuite::class,
                'description' => 'rex_yform_manager_table cache layer (3.U.7)',
            ],
            'field-types' => [
                'class'       => FieldTypesSuite::class,
                'description' => 'Type-specific behavior datestamp/choice/uuid/checkbox/etc (3.U.4)',
            ],
            // Add further suites here as they get implemented:
            // 'tablesets'        => [...]
            // 'e2e'              => [...]
        ];
    }

    /**
     * @return class-string<AbstractTestSuite>
     */
    public static function get(string $key): string
    {
        $all = self::all();
        if (!isset($all[$key])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown test suite "%s". Available: %s',
                $key,
                implode(', ', array_keys($all)),
            ));
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
