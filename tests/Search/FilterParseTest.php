<?php

namespace Yakamara\YForm\Tests\Search;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yakamara\YForm\Field\Checkbox;
use Yakamara\YForm\Field\Choice;
use Yakamara\YForm\Field\CreatedAt;
use Yakamara\YForm\Field\Date;
use Yakamara\YForm\Field\DateTime;
use Yakamara\YForm\Field\Decimal;
use Yakamara\YForm\Field\Integer;
use Yakamara\YForm\Field\Password;
use Yakamara\YForm\Field\Text;
use Yakamara\YForm\Field\Time;
use Yakamara\YForm\Search\BooleanFilter;
use Yakamara\YForm\Search\ChoiceFilter;
use Yakamara\YForm\Search\RangeFilter;
use Yakamara\YForm\Search\TextFilter;

/**
 * @internal
 */
final class FilterParseTest extends TestCase
{
    public function testFieldsAreNotFilterableByDefault(): void
    {
        self::assertFalse(Text::new('title')->filterable);
        self::assertTrue(Text::new('title')->filterable()->filterable);
    }

    public function testFilterTypePerField(): void
    {
        self::assertInstanceOf(TextFilter::class, Text::new('a')->searchFilter());
        self::assertInstanceOf(RangeFilter::class, Integer::new('a')->searchFilter());
        self::assertInstanceOf(RangeFilter::class, Date::new('a')->searchFilter());
        self::assertInstanceOf(ChoiceFilter::class, Choice::new('a')->options(['x' => 'X'])->searchFilter());
        self::assertInstanceOf(BooleanFilter::class, Checkbox::new('a')->searchFilter());
        self::assertNull(Password::new('a')->searchFilter());
    }

    /** @return iterable<string, array{string, array{negate: bool, pattern: ?string}|null}> */
    public static function textInputs(): iterable
    {
        yield 'empty input' => ['  ', null];
        yield 'contains' => ['tee', ['negate' => false, 'pattern' => '%tee%']];
        yield 'wildcard' => ['tee*', ['negate' => false, 'pattern' => 'tee%']];
        yield 'exact' => ['"Tee"', ['negate' => false, 'pattern' => 'Tee']];
        yield 'empty field' => ['""', ['negate' => false, 'pattern' => null]];
        yield 'negated' => ['!tee', ['negate' => true, 'pattern' => '%tee%']];
        yield 'negated empty' => ['!""', ['negate' => true, 'pattern' => null]];
        yield 'lone negation' => ['!', null];
        yield 'like characters are literal' => ['50%_a', ['negate' => false, 'pattern' => '%50\%\_a%']];
    }

    /** @param array{negate: bool, pattern: ?string}|null $expected */
    #[DataProvider('textInputs')]
    public function testTextSyntax(string $input, ?array $expected): void
    {
        self::assertSame($expected, new TextFilter(Text::new('title'))->parse($input));
    }

    public function testTextIgnoresArrays(): void
    {
        self::assertNull(new TextFilter(Text::new('title'))->parse(['tee']));
    }

    public function testIntegerRange(): void
    {
        $filter = Integer::new('stock')->searchFilter();
        self::assertSame(['from' => 5, 'to' => null], $filter?->parse(['from' => '5', 'to' => '']));
        self::assertNull($filter?->parse(['from' => 'abc']));
        self::assertNull($filter?->parse('5'));
    }

    public function testDecimalAcceptsCommaInput(): void
    {
        self::assertSame(['from' => '1234.5', 'to' => null], Decimal::new('price')->searchFilter()?->parse(['from' => '1.234,5']));
    }

    public function testDateRangeIsStrict(): void
    {
        $filter = Date::new('day')->searchFilter();
        self::assertSame(['from' => '2026-01-01', 'to' => '2026-01-31'], $filter?->parse(['from' => '2026-01-01', 'to' => '2026-01-31']));
        self::assertNull($filter?->parse(['from' => '2026-02-31']));
        self::assertNull($filter?->parse(['from' => 'tomorrow']));
    }

    public function testDateTimeRangeCoversWholeDays(): void
    {
        $expected = ['from' => '2026-01-01 00:00:00', 'to' => '2026-01-31 23:59:59'];
        self::assertSame($expected, DateTime::new('at')->searchFilter()?->parse(['from' => '2026-01-01', 'to' => '2026-01-31']));
        self::assertSame($expected, CreatedAt::create()->searchFilter()?->parse(['from' => '2026-01-01', 'to' => '2026-01-31']));
    }

    public function testTimeRange(): void
    {
        self::assertSame(['from' => '08:00:00', 'to' => '12:30:59'], Time::new('start')->searchFilter()?->parse(['from' => '08:00', 'to' => '12:30']));
        self::assertNull(Time::new('start')->searchFilter()?->parse(['from' => '25:00']));
    }

    public function testChoiceKeepsOnlyKnownOptionsWithOriginalKeys(): void
    {
        $filter = Choice::new('status')->options(['new' => 'Neu', 1 => 'Eins'])->searchFilter();
        self::assertSame(['new', 1], $filter?->parse(['1', 'new', 'unknown']));
        self::assertNull($filter?->parse(['unknown']));
        self::assertSame(['new'], $filter?->parse('new'));
    }

    public function testBoolean(): void
    {
        $filter = Checkbox::new('active')->searchFilter();
        self::assertTrue($filter?->parse('1'));
        self::assertFalse($filter?->parse('0'));
        self::assertNull($filter?->parse(''));
    }
}
