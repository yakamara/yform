<?php

namespace Yakamara\YForm\Tests\Form;

use PHPUnit\Framework\TestCase;
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
    }

    public function testPlainThemeRendersTabsAsSections(): void
    {
        self::assertFalse(Theme::plain()->supportsTabs());
        self::assertTrue(Theme::bootstrap5()->supportsTabs());
    }
}
