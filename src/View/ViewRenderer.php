<?php

namespace Yakamara\YForm\View;

use Closure;
use Redaxo\Core\View\ViewResolver;
use RuntimeException;
use Throwable;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Form\FieldContext;

use function get_parent_class;
use function is_file;
use function ob_get_clean;
use function ob_start;
use function sprintf;

/**
 * Renders the co-located `ClassName.view.php` of a class.
 *
 * Unlike the core resolver it walks up the class hierarchy, so `Email extends Text` reuses the text view until
 * somebody provides `Email.view.php`. Projects replace any view with `ViewResolver::override(Text::class, $file)`.
 *
 * A view file returns a closure; its parameters receive the arguments passed to {@see render()}.
 *
 * @internal
 */
final class ViewRenderer
{
    /** @var array<class-string, string> */
    private static array $paths = [];

    public static function field(Field $field, FieldContext $context): string
    {
        return self::render($field::class, $field, $context);
    }

    /** @param class-string $class */
    public static function render(string $class, mixed ...$args): string
    {
        $path = self::$paths[$class] ??= self::locate($class);

        $closure = require $path;
        if (!$closure instanceof Closure) {
            throw new RuntimeException(sprintf('View file "%s" must return a closure.', $path));
        }

        ob_start();
        try {
            $closure(...$args);
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    /** @param class-string $class */
    private static function locate(string $class): string
    {
        for ($current = $class; false !== $current; $current = get_parent_class($current)) {
            $path = ViewResolver::resolve($current);
            if (is_file($path)) {
                return $path;
            }
        }
        throw new RuntimeException(sprintf('No view found for "%s" or one of its parents.', $class));
    }
}
