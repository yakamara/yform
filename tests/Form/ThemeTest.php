<?php

namespace Yakamara\YForm\Tests\Form;

use PHPUnit\Framework\TestCase;
use Yakamara\YForm\Field\Text;
use Yakamara\YForm\Form\Form;
use Yakamara\YForm\Form\Layout\Cell;
use Yakamara\YForm\Form\Layout\Layout;
use Yakamara\YForm\Form\Theme\Theme;

/**
 * @internal
 */
final class ThemeTest extends TestCase
{
    public function testColumnClass(): void
    {
        self::assertSame('col-sm-6', Theme::bootstrap3()->columnClass(6));
        self::assertSame('col-md-4', Theme::bootstrap5()->columnClass(4));
        self::assertSame('col-span-12 md:col-span-4', Theme::tailwind()->columnClass(4));
        self::assertSame('col-span-12', Theme::tailwind()->columnClass(12));
    }

    public function testColumnClassAcceptsClosure(): void
    {
        $theme = new Theme(column: static fn (int $width): string => 'col-12 col-lg-' . $width);
        self::assertSame('col-12 col-lg-6', $theme->columnClass(6));
        self::assertStringContainsString('class="col-12 col-lg-8"', self::render($theme));
    }

    public function testFrameworkTabsUseThePluginAttribute(): void
    {
        $html = self::render(Theme::bootstrap5());

        self::assertFalse(Theme::bootstrap5()->usesOwnTabs());
        self::assertStringContainsString('data-bs-toggle="tab"', $html);
        self::assertStringNotContainsString('data-yform-tab', $html);
        self::assertDoesNotMatchRegularExpression('/<ul [^>]*hidden/', $html);
    }

    public function testOwnTabsStayUsableWithoutJavaScript(): void
    {
        $html = self::render(Theme::plain());

        self::assertTrue(Theme::plain()->supportsTabs());
        self::assertTrue(Theme::plain()->usesOwnTabs());
        // The navigation is hidden until the script takes over, the panes are not, so every field stays reachable.
        self::assertMatchesRegularExpression('/<ul [^>]*data-yform-tabs hidden>/', $html);
        self::assertStringContainsString('data-yform-tab data-yform-tab-active="is-active" role="tab" aria-selected="true"', $html);
        self::assertStringNotContainsString('role="tabpanel" hidden', $html);
        self::assertSame(2, substr_count($html, 'class="yform-tab-heading"'));
        self::assertStringNotContainsString('data-toggle', $html);
    }

    public function testBackendUsesTheCoreComponents(): void
    {
        $html = self::render(Theme::backend());

        // the columns are narrow, so the labels stay above the fields
        self::assertStringContainsString('<div data-layout="vertical" class="rex-field yform-field-a">', $html);
        self::assertStringContainsString('<div class="rex-grid yform-grid"><div data-span="sm:8">', $html);
        self::assertStringContainsString('<button data-variant="success" type="submit" name="t[_action]" value="save" class="rex-button">', $html);
        self::assertStringContainsString('class="rex-tabs-link" href="#t-tab-0" data-yform-tab role="tab" aria-selected="true"', $html);
        self::assertDoesNotMatchRegularExpression('/class="(?:[^"]* )?(?:form-|col-|btn|row[ "])/', $html);
    }

    public function testAttributesMergeThePartsWithTheView(): void
    {
        $theme = Theme::backend();

        self::assertSame(
            'data-size="xs" type="button" class="rex-button yform-x"',
            $theme->attributes(['secondary', 'buttonSmall'], ['type' => 'button', 'class' => ['yform-x']])->toString(),
        );
        self::assertSame('class="col-sm-4"', Theme::bootstrap3()->columnAttributes(4)->toString());
    }

    private static function render(Theme $theme): string
    {
        $layout = new Layout()
            ->tab('Eins')->row(Cell::field('a', 8), Cell::field('b', 4))
            ->tab('Zwei')->row('c');
        $form = new Form('t', [Text::new('a'), Text::new('b'), Text::new('c')], $layout, $theme);
        $form->csrf = false;
        return $form->render();
    }
}
