<?php

namespace Yakamara\YForm;

use Redaxo\Core\ClassDiscovery;
use Yakamara\YForm\Action\AbstractAction;
use Yakamara\YForm\Attribute\AsAction;
use Yakamara\YForm\Attribute\AsValidate;
use Yakamara\YForm\Attribute\AsValue;
use Yakamara\YForm\Validate\AbstractValidate;
use Yakamara\YForm\Value\AbstractValue;

use function class_exists;
use function is_subclass_of;

/**
 * Resolves a field type name ("text", "email", "unique", "db", …) to the class implementing it.
 *
 * REDAXO 5 derived this from `rex_autoload::getClasses()` by scanning for the class-name prefixes
 * `rex_yform_value_` / `_validate_` / `_action_`. REDAXO 6 has no such class index — it autoloads
 * through Composer — so the mapping is declared explicitly with the
 * {@see AsValue} / {@see AsValidate} / {@see AsAction} attributes and discovered via
 * {@see ClassDiscovery}, the same mechanism core uses for modules, templates, meta schemas and
 * console commands.
 *
 * The *type names* stay exactly what they were, because they are persisted in
 * `rex_yform_field.type_name` and written by hand in pipe-notation form definitions.
 */
final class FieldRegistry
{
    /** @var array<string, array<string, class-string>>|null type => name => class */
    private static ?array $classes = null;

    /**
     * Legacy naming convention of REDAXO 5, kept as a fallback so a custom field class that a project
     * carried over unchanged (`class rex_yform_value_myfield`) still resolves without an attribute.
     */
    private const array LEGACY_PREFIX = [
        'value' => 'rex_yform_value_',
        'validate' => 'rex_yform_validate_',
        'action' => 'rex_yform_action_',
    ];

    private const array BASE_CLASS = [
        'value' => AbstractValue::class,
        'validate' => AbstractValidate::class,
        'action' => AbstractAction::class,
    ];

    /**
     * @param 'value'|'validate'|'action' $type
     * @return class-string|null
     */
    public static function getClass(string $type, string $name): ?string
    {
        $class = self::all()[$type][$name] ?? null;

        if (null !== $class) {
            return $class;
        }

        // REDAXO 5 style custom field, not carrying an attribute
        $legacy = (self::LEGACY_PREFIX[$type] ?? '') . $name;
        if ('' !== $legacy && class_exists($legacy) && is_subclass_of($legacy, self::BASE_CLASS[$type])) {
            return $legacy;
        }

        return null;
    }

    /** @param 'value'|'validate'|'action' $type */
    public static function exists(string $type, string $name): bool
    {
        return null !== self::getClass($type, $name);
    }

    /**
     * @param 'value'|'validate'|'action' $type
     * @return array<string, class-string> name => class, sorted by name
     */
    public static function getClasses(string $type): array
    {
        return self::all()[$type] ?? [];
    }

    /** @return array<string, array<string, class-string>> */
    public static function all(): array
    {
        if (null !== self::$classes) {
            return self::$classes;
        }

        $discovery = ClassDiscovery::getInstance();

        $classes = [
            'value' => self::collect($discovery->discoverByAttribute(AsValue::class, AbstractValue::class)),
            'validate' => self::collect($discovery->discoverByAttribute(AsValidate::class, AbstractValidate::class)),
            'action' => self::collect($discovery->discoverByAttribute(AsAction::class, AbstractAction::class)),
        ];

        return self::$classes = $classes;
    }

    /**
     * @param array<class-string, AsValue|AsValidate|AsAction> $discovered
     * @return array<string, class-string>
     */
    private static function collect(array $discovered): array
    {
        $result = [];

        foreach ($discovered as $class => $attribute) {
            $result[$attribute->name] = $class;
        }

        ksort($result);

        return $result;
    }

    /** Only for tests — the discovery result is cached for the whole request otherwise. */
    public static function reset(): void
    {
        self::$classes = null;
    }
}
