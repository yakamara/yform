<?php

namespace Yakamara\YForm\Email;

use function addcslashes;
use function array_key_exists;
use function nl2br;
use function ob_get_clean;
use function ob_start;
use function preg_match_all;
use function preg_replace_callback;
use function str_replace;
use function strlen;
use function substr;

use function Redaxo\Core\View\escape;

/**
 * Renders an e-mail template: resolves its `REX_YFORM_DATA[…]` placeholders and evaluates the result.
 *
 * REDAXO 5 did this in two steps that no longer exist in REDAXO 6:
 *
 *  1. `rex_var::parse($body, null, 'yform_email_template', $values)` tokenised the template as PHP and
 *     let `rex_var_yform_data` rewrite each `REX_YFORM_DATA[…]` into a **quoted PHP string literal** —
 *     which is why the documented `if ('REX_YFORM_DATA[field="anrede"]' == 'w')` works.
 *  2. `rex_file::getOutput(rex_stream::factory(…, $body))` then *executed* that PHP through a `rex://`
 *     stream wrapper, so templates may contain PHP.
 *
 * REDAXO 6 removed the whole REX_VAR system (only four widget helpers remain) and has no stream
 * wrapper at all. Both steps are reimplemented here, keeping the template syntax and semantics
 * unchanged so existing templates continue to work.
 *
 * ## Why this evaluates PHP
 *
 * Executing the template is a documented yform feature (docs/02_email.md has a "PHP" section) and is
 * what REDAXO 5 did — a `rex://` stream include runs the same code an `eval()` does, so this is not a
 * new capability. Two boundaries keep submitted form data out of the executed code, both inherited
 * from rex_var_yform_data:
 *
 *  - every substituted value is emitted as a single-quoted PHP literal via {@see self::quote()}, so a
 *    value cannot terminate the literal or append statements;
 *  - in plain mode `<?`/`?>` inside a value are neutralised, so a value cannot open a PHP block.
 *
 * What remains is that a template *author* can run PHP. That is inherent to the feature and no
 * different from a module or template in REDAXO itself; the e-mail template page is gated behind the
 * `admin[]` permission. If a project does not want that, it should not grant that permission.
 */
final class TemplateRenderer
{
    /**
     * Resolves the placeholders of $content against $values and returns the evaluated output.
     *
     * @param array<string, mixed> $values field name => value, plus the REX_* extras
     */
    public static function render(string $content, array $values): string
    {
        return self::evaluate(self::replacePlaceholders($content, $values));
    }

    /**
     * Rewrites every `REX_YFORM_DATA[…]` occurrence into a quoted PHP string literal.
     *
     * Supported arguments, matching rex_var_yform_data:
     *   field="name"  the value to insert (also accepted as the first, unnamed argument)
     *   output=html   escape and nl2br the value; anything else (default) inserts it plain with
     *                 `<?`/`?>` neutralised so a value can never open a PHP block
     *   isset=1       insert the literal true/false instead of the value
     *
     * @param array<string, mixed> $values
     */
    public static function replacePlaceholders(string $content, array $values): string
    {
        return (string) preg_replace_callback(
            '/REX_YFORM_DATA\[([^\]]*)\]/',
            static function (array $match) use ($values): string {
                $args = self::parseArgs($match[1]);
                $field = $args['field'] ?? $args[0] ?? '';
                $value = $values[$field] ?? '';

                if (!is_scalar($value) && null !== $value) {
                    $value = '';
                }
                $value = (string) $value;

                if (array_key_exists('isset', $args) && $args['isset']) {
                    return '' !== $value ? 'true' : 'false';
                }

                if ('html' === ($args['output'] ?? '')) {
                    $value = nl2br((string) escape($value));
                } else {
                    $value = str_replace(['<?', '?>'], ['&lt;?', '?&gt;'], $value);
                }

                return self::quote($value);
            },
            $content,
        );
    }

    /**
     * Parses `field="name" output=html isset=1` into a map. Unnamed values land under numeric keys, so
     * `REX_YFORM_DATA[name]` keeps working like REDAXO 5's default argument.
     *
     * @return array<array-key, string>
     */
    private static function parseArgs(string $raw): array
    {
        $args = [];
        $index = 0;

        preg_match_all('/(?:(\w+)\s*=\s*)?(?:"([^"]*)"|\'([^\']*)\'|([^\s]+))/', $raw, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $value = '' !== $m[2] ? $m[2] : ('' !== ($m[3] ?? '') ? $m[3] : ($m[4] ?? ''));
            if ('' !== $m[1]) {
                $args[$m[1]] = $value;
            } else {
                $args[$index++] = $value;
            }
        }

        return $args;
    }

    /** Same escaping as rex_var::quote(): a single-quoted PHP literal. */
    private static function quote(string $string): string
    {
        return "'" . addcslashes($string, "\\'") . "'";
    }

    /**
     * Executes the template as PHP and returns its output, the way including a `rex://` stream did.
     * The leading `?>` puts the evaluated code into HTML mode, so plain text is echoed as-is.
     */
    private static function evaluate(string $code): string
    {
        ob_start();

        try {
            // Intentional: an e-mail template may contain PHP, which was true in REDAXO 5 as well.
            eval('?>' . $code);
        } finally {
            $output = ob_get_clean();
        }

        return false === $output ? '' : $output;
    }
}
