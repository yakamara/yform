<?php

namespace Yakamara\YForm\Table;

use InvalidArgumentException;
use Redaxo\Core\ClassDiscovery;
use Yakamara\YForm\Attribute\AsTable;

use function sprintf;
use function uasort;

/**
 * Registry of all tables defined via `#[AsTable]` in the project and in addons.
 */
final class Tables
{
    /** @var array<string, Table>|null keyed by table name */
    private static ?array $tables = null;

    /** @var array<class-string, string> class → table name */
    private static array $classes = [];

    /** @return array<string, Table> keyed by table name, sorted by prio and label */
    public static function all(): array
    {
        if (null !== self::$tables) {
            return self::$tables;
        }

        $tables = [];
        foreach (ClassDiscovery::getInstance()->discoverByAttribute(AsTable::class, Table::class) as $class => $attribute) {
            if (isset($tables[$attribute->name])) {
                throw new InvalidArgumentException(sprintf('The table "%s" is defined twice (%s, %s).', $attribute->name, $tables[$attribute->name]::class, $class));
            }
            $tables[$attribute->name] = new $class($attribute);
            self::$classes[$class] = $attribute->name;
        }

        uasort($tables, static fn (Table $a, Table $b): int => [$a->definition->prio, $a->label()] <=> [$b->definition->prio, $b->label()]);

        return self::$tables = $tables;
    }

    /** @param string|class-string<Table> $nameOrClass */
    public static function find(string $nameOrClass): ?Table
    {
        $tables = self::all();
        return $tables[$nameOrClass] ?? $tables[self::$classes[$nameOrClass] ?? ''] ?? null;
    }

    /** @param string|class-string<Table> $nameOrClass */
    public static function get(string $nameOrClass): Table
    {
        return self::find($nameOrClass) ?? throw new InvalidArgumentException(sprintf('Unknown table "%s". Is the class marked with #[AsTable] and the discovery cache up to date?', $nameOrClass));
    }

    /** @internal for tests */
    public static function reset(): void
    {
        self::$tables = null;
        self::$classes = [];
    }
}
