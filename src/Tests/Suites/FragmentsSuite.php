<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Core;
use Redaxo\Core\Environment;
use Redaxo\Core\Exception\RuntimeException;
use Yakamara\YForm\Test\AbstractTestSuite;
use Yakamara\YForm\View\Fragment;
use Yakamara\YForm\YForm;

use function count;
use function in_array;

/**
 * The fragment layer that replaced yform 5's ytemplates.
 *
 * Two properties matter and are easy to break by accident:
 *   - every backend fragment has a frontend twin, and the twin only delegates, so
 *     both contexts render the same markup until someone changes the frontend file,
 *   - a fragment gets its variables as plain locals ($yfield / $yform / the caller's
 *     parameters), because the templates were written against parse()'s extract().
 *
 * @package redaxo\yform
 * @internal
 */
final class FragmentsSuite extends AbstractTestSuite
{
    /** Runs $fn with the environment forced to $env. */
    private function inEnv(Environment $env, callable $fn): mixed
    {
        // getEnvironment() answers Console first while a console app is registered and
        // falls back to the `redaxo` property, so both have to be set.
        $console = Core::getProperty('console');
        $redaxo = Core::getProperty('redaxo');
        Core::setProperty('console', null);
        Core::setProperty('redaxo', Environment::Backend === $env);

        try {
            return $fn();
        } finally {
            Core::setProperty('console', $console);
            Core::setProperty('redaxo', $redaxo);
        }
    }

    /** A small form covering a few different field templates. */
    private function renderForm(string $name): string
    {
        $yform = new YForm();
        $yform->setObjectparams('form_name', $name);
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('form_exit', false);
        $yform->setValueField('text', ['vorname', 'Vorname']);
        $yform->setValueField('textarea', ['nachricht', 'Nachricht']);
        $yform->setValueField('choice', ['farbe', 'Farbe', 'rot=Rot,blau=Blau']);
        $yform->setValueField('submit', ['send', 'Absenden', '', 'no_db']);

        return $yform->getForm();
    }

    private function fragmentPath(string $relative): string
    {
        return Addon::require('yform')->getPath('fragments/' . $relative);
    }

    // ---------- layout ----------

    public function testEveryBackendFragmentHasAFrontendTwin(): void
    {
        foreach (['form', 'value'] as $group) {
            $backendDir = $this->fragmentPath('yform/' . $group . '/backend');
            $files = glob($backendDir . '/*.php') ?: [];

            $this->assertTrue(count($files) > 0, 'no backend fragments in group ' . $group);

            foreach ($files as $file) {
                $twin = $this->fragmentPath('yform/' . $group . '/frontend/' . basename($file));
                $this->assertTrue(
                    is_readable($twin),
                    sprintf('frontend twin missing for %s/%s', $group, basename($file)),
                );
            }
        }
    }

    public function testFrontendFragmentsOnlyDelegate(): void
    {
        foreach (['form', 'value'] as $group) {
            foreach (glob($this->fragmentPath('yform/' . $group . '/frontend') . '/*.php') ?: [] as $file) {
                $body = (string) file_get_contents($file);
                $this->assertStringContains(
                    "delegate('yform/" . $group . '/backend/' . basename($file) . "')",
                    $body,
                    sprintf('%s should hand over to its backend twin', basename($file)),
                );
            }
        }
    }

    // ---------- resolution ----------

    public function testResolvePicksTheContextVariant(): void
    {
        $this->assertSame(
            'yform/value/backend/text.php',
            Fragment::resolve('text', 'value', Fragment::CONTEXT_BACKEND),
        );
        $this->assertSame(
            'yform/value/frontend/text.php',
            Fragment::resolve('text', 'value', Fragment::CONTEXT_FRONTEND),
        );
    }

    /** The value classes pass a list to fall back from a specialised view onto the generic one. */
    public function testResolveWalksTheCandidateList(): void
    {
        $this->assertSame(
            'yform/value/backend/view.php',
            Fragment::resolve(['be_link.view', 'view'], 'value', Fragment::CONTEXT_BACKEND),
        );
    }

    /** The form template renders the fieldset field, which lives in the value group. */
    public function testResolveAcceptsAGroupQualifiedKey(): void
    {
        $this->assertSame(
            'yform/value/backend/fieldset.php',
            Fragment::resolve('value/fieldset', 'form', Fragment::CONTEXT_BACKEND),
        );
    }

    public function testResolveReturnsNullForAnUnknownKey(): void
    {
        $this->assertNull(Fragment::resolve('does-not-exist', 'value', Fragment::CONTEXT_BACKEND));
    }

    public function testContextFollowsTheEnvironment(): void
    {
        $this->assertSame(
            Fragment::CONTEXT_BACKEND,
            $this->inEnv(Environment::Backend, Fragment::currentContext(...)),
        );
        $this->assertSame(
            Fragment::CONTEXT_FRONTEND,
            $this->inEnv(Environment::Frontend, Fragment::currentContext(...)),
        );
    }

    // ---------- rendering ----------

    public function testFormRendersInBothContexts(): void
    {
        foreach ([Environment::Backend, Environment::Frontend] as $env) {
            $html = $this->inEnv($env, fn () => $this->renderForm('frag_' . $env->value));

            foreach (['Vorname', 'Nachricht', 'Farbe', 'Absenden'] as $needle) {
                $this->assertStringContains($needle, $html, sprintf('%s missing in %s output', $needle, $env->value));
            }
        }
    }

    /**
     * The delegating frontend twin has to produce the backend markup. `choice` is the
     * one field that legitimately differs — it wraps its select in .rex-select-style
     * for the backend only — so compare a form without it.
     */
    public function testFrontendDelegationYieldsTheBackendMarkup(): void
    {
        $render = function (string $name): string {
            $yform = new YForm();
            $yform->setObjectparams('form_name', $name);
            $yform->setObjectparams('csrf_protection', false);
            $yform->setObjectparams('form_exit', false);
            $yform->setValueField('text', ['vorname', 'Vorname']);
            $yform->setValueField('textarea', ['nachricht', 'Nachricht']);

            return $yform->getForm();
        };

        $backend = $this->inEnv(Environment::Backend, static fn () => $render('same'));
        $frontend = $this->inEnv(Environment::Frontend, static fn () => $render('same'));

        $this->assertSame($backend, $frontend);
    }

    /** Replacing a frontend fragment must affect the frontend only. */
    public function testFrontendFragmentCanOverrideTheBackendMarkup(): void
    {
        $path = $this->fragmentPath('yform/value/frontend/text.php');
        $backup = (string) file_get_contents($path);
        file_put_contents($path, '<?php echo "OVERRIDE-MARKER";');

        try {
            $frontend = $this->inEnv(Environment::Frontend, fn () => $this->renderForm('ovr_f'));
            $backend = $this->inEnv(Environment::Backend, fn () => $this->renderForm('ovr_b'));

            $this->assertStringContains('OVERRIDE-MARKER', $frontend);
            $this->assertFalse(str_contains($backend, 'OVERRIDE-MARKER'), 'the backend must be unaffected');
        } finally {
            file_put_contents($path, $backup);
        }
    }

    /** Variables reach a fragment as locals, not only as $this->name. */
    public function testFragmentReceivesItsVariablesAsLocals(): void
    {
        $fragment = new Fragment(['yfield' => null, 'greeting' => 'hallo']);

        $this->assertSame(['yfield' => null, 'greeting' => 'hallo'], $fragment->getVariables());
    }

    public function testUnknownFragmentKeyThrows(): void
    {
        $this->assertThrows(
            RuntimeException::class,
            static fn () => Fragment::render('there-is-no-such-template', 'value', []),
        );
    }

    // ---------- the ytemplate machinery is gone ----------

    public function testNoYtemplateLeftovers(): void
    {
        $addon = Addon::require('yform');

        $this->assertFalse(is_dir($addon->getPath('ytemplates')), 'the ytemplates directory should be gone');
        $this->assertFalse(method_exists(YForm::class, 'addTemplatePath'), 'addTemplatePath should be gone');
        $this->assertFalse(method_exists(YForm::class, 'getTemplatePath'), 'getTemplatePath should be gone');

        $yform = new YForm();
        $this->assertFalse(
            in_array('form_ytemplate', array_keys($yform->objparams), true),
            'the form_ytemplate objparam should be gone',
        );
    }
}
