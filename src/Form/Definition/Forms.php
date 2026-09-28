<?php

namespace Yakamara\YForm\Form\Definition;

use InvalidArgumentException;
use Redaxo\Core\ClassDiscovery;
use Yakamara\YForm\Attribute\AsForm;
use Yakamara\YForm\Form\Theme\Theme;

use function sprintf;

/**
 * Registry of all forms defined via `#[AsForm]`.
 */
final class Forms
{
    /**
     * Theme of frontend forms unless a form overrides {@see FormDefinition::theme()}; set it in the project boot.
     * Default: Bootstrap 5.
     */
    public static ?Theme $defaultTheme = null;

    /** @var array<string, FormDefinition>|null */
    private static ?array $forms = null;

    /** @var array<class-string, string> */
    private static array $classes = [];

    /** @return array<string, FormDefinition> */
    public static function all(): array
    {
        if (null !== self::$forms) {
            return self::$forms;
        }
        $forms = [];
        foreach (ClassDiscovery::getInstance()->discoverByAttribute(AsForm::class, FormDefinition::class) as $class => $attribute) {
            if (isset($forms[$attribute->name])) {
                throw new InvalidArgumentException(sprintf('The form "%s" is defined twice (%s, %s).', $attribute->name, $forms[$attribute->name]::class, $class));
            }
            $forms[$attribute->name] = new $class($attribute);
            self::$classes[$class] = $attribute->name;
        }
        ksort($forms);
        return self::$forms = $forms;
    }

    /** @param string|class-string<FormDefinition> $nameOrClass */
    public static function find(string $nameOrClass): ?FormDefinition
    {
        $forms = self::all();
        return $forms[$nameOrClass] ?? $forms[self::$classes[$nameOrClass] ?? ''] ?? null;
    }

    /** @param string|class-string<FormDefinition> $nameOrClass */
    public static function get(string $nameOrClass): FormDefinition
    {
        return self::find($nameOrClass) ?? throw new InvalidArgumentException(sprintf('Unknown form "%s".', $nameOrClass));
    }
}
