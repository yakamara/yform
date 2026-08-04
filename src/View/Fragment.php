<?php

namespace Yakamara\YForm\View;

use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Core;
use Redaxo\Core\Exception\RuntimeException;
use Redaxo\Core\View\Fragment as CoreFragment;

/**
 * The fragment yform renders its form markup with.
 *
 * Adds two things to the core fragment:
 *
 *  - {@see self::delegate()}, so a frontend fragment can hand over to its backend
 *    counterpart instead of repeating its markup,
 *  - {@see self::resolve()}, which turns a short template key ("text", "be_link.view")
 *    into a fragment path for the current context.
 *
 * Layout of the fragment tree:
 *
 *     fragments/yform/
 *       form/backend/form.php       the <form> wrapper
 *       form/frontend/form.php      delegates to the backend one
 *       value/backend/text.php      one file per field template
 *       value/frontend/text.php     delegates to the backend one
 *
 * Every backend file has a frontend counterpart, so a project can replace a single
 * one without touching the rest. Out of the box each frontend file just delegates.
 *
 * @package redaxo\yform
 */
final class Fragment extends CoreFragment
{
    public const CONTEXT_BACKEND = 'backend';
    public const CONTEXT_FRONTEND = 'frontend';

    /**
     * Fragment base directories of addons that ship yform field templates of their own.
     *
     * Core's fragment loader already searches every enlisted addon when *parsing*, but it keeps its
     * directory list private, so {@see self::resolve()} cannot ask it whether a candidate exists.
     * An addon that adds a value field with its own template registers its fragment directory here
     * — see {@see self::addSearchPath()}.
     *
     * @var list<string>
     */
    private static array $searchPaths = [];

    /**
     * Core keeps its vars private, so hold on to them here: delegate() has to pass
     * the same set on to the fragment it hands over to.
     *
     * @var array<string, mixed>
     */
    private array $data;

    /** @param array<string, mixed> $vars */
    public function __construct(array $vars = [])
    {
        parent::__construct($vars);
        $this->data = $vars;
    }

    #[\Override]
    public function setVar(string $name, mixed $value, bool $escape = true): static
    {
        parent::setVar($name, $value, $escape);
        $this->data[$name] = $this->getVar($name);

        return $this;
    }

    /**
     * The variables this fragment was created with.
     *
     * Core hands variables to a fragment as `$this->name`. yform's field templates
     * expect them as plain locals instead — they came from a `parse()` that did an
     * `extract()` — so every template opens with `extract($this->getVariables())`.
     * Keeping that shape means the templates read the same as before and there is no
     * guessing about which name is a parameter and which one is a local.
     *
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->data;
    }

    /**
     * Renders another fragment with this fragment's variables.
     *
     * What the frontend templates fall back on the backend markup with:
     *
     *     echo $this->delegate('yform/value/backend/text.php');
     */
    public function delegate(string $filename): string
    {
        return new self($this->data)->parse($filename);
    }

    /**
     * Resolves a template key and renders it.
     *
     * @param string|list<string> $key one key, or candidates to fall back through
     * @param 'form'|'value' $group
     * @param array<string, mixed> $vars
     */
    public static function render(string|array $key, string $group, array $vars): string
    {
        $path = self::resolve($key, $group, self::currentContext());

        if (null === $path) {
            throw new RuntimeException(sprintf(
                'yform fragment "%s" not found in group "%s". Expected a file at fragments/yform/%s/{backend,frontend}/<key>.php',
                implode('", "', (array) $key),
                $group,
                $group,
            ));
        }

        return new self($vars)->parse($path);
    }

    /**
     * Turns a template key into a fragment path for the given context.
     *
     * Accepts a single key or a list and returns the first one that exists — the
     * value classes use that to fall back from a specialised view template onto the
     * generic one (["be_link.view", "view"]).
     *
     * Existence is checked against yform's own fragment directory plus the directories
     * registered via {@see self::addSearchPath()}: a project can override any of these
     * files by shipping the same path, and an addon that adds its own value field can
     * ship that field's template — but a key nothing renders still resolves to null.
     *
     * @param string|list<string> $key
     * @param self::CONTEXT_* $context
     */
    public static function resolve(string|array $key, string $group, string $context): ?string
    {
        $bases = [Addon::require('yform')->getPath('fragments/'), ...self::$searchPaths];

        foreach ((array) $key as $candidate) {
            // A key may name its own group ("value/fieldset"): the form template renders
            // the fieldset field, which lives in the value group.
            $candidateGroup = $group;
            if (str_contains($candidate, '/')) {
                [$candidateGroup, $candidate] = explode('/', $candidate, 2);
            }

            foreach ([$context, self::CONTEXT_BACKEND] as $ctx) {
                $path = sprintf('yform/%s/%s/%s.php', $candidateGroup, $ctx, $candidate);

                foreach ($bases as $base) {
                    if (is_readable($base . $path)) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Registers an additional fragment base directory, so an addon can ship templates for
     * the yform value fields it adds.
     *
     * Called from the addon's `boot()` with its own fragment directory:
     *
     *     Fragment::addSearchPath($this->getPath('fragments/'));
     *
     * The files go to the paths yform uses (`yform/value/frontend/<key>.php`); core's
     * fragment loader finds them because {@see Addon::enlist()} registered the directory.
     */
    public static function addSearchPath(string $dir): void
    {
        $dir = rtrim($dir, '/\\') . '/';

        if (!in_array($dir, self::$searchPaths, true)) {
            self::$searchPaths[] = $dir;
        }
    }

    /** The context the current request renders in. */
    public static function currentContext(): string
    {
        return Core::isBackend() ? self::CONTEXT_BACKEND : self::CONTEXT_FRONTEND;
    }
}
