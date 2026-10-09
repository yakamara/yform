<?php

namespace Yakamara\YForm\View;

use Closure;
use RuntimeException;
use Throwable;

use function is_file;
use function ob_get_clean;
use function ob_start;
use function sprintf;

/**
 * Renders a template file that returns a closure (same convention as the co-located views), passing the given
 * arguments. Used for mail templates.
 *
 * @internal
 */
final class TemplateRenderer
{
    public static function render(string $file, mixed ...$args): string
    {
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Template "%s" not found.', $file));
        }
        $closure = require $file;
        if (!$closure instanceof Closure) {
            throw new RuntimeException(sprintf('Template "%s" must return a closure.', $file));
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
}
