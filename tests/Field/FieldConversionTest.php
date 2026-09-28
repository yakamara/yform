<?php

namespace Yakamara\YForm\Tests\Field;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yakamara\YForm\Field\Checkbox;
use Yakamara\YForm\Field\Choice;
use Yakamara\YForm\Field\Date;
use Yakamara\YForm\Field\DateTime;
use Yakamara\YForm\Field\Decimal;
use Yakamara\YForm\Field\Email;
use Yakamara\YForm\Field\Integer;
use Yakamara\YForm\Field\Text;
use Yakamara\YForm\Field\Time;
use Yakamara\YForm\Field\Uuid;

enum Colour: string
{
    case Red = 'red';
    case Blue = 'blue';

    public function label(): string
    {
        return 'Farbe ' . $this->name;
    }
}

/**
 * @internal
 */
final class FieldConversionTest extends TestCase
{
    public function testInvalidFieldNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Text::new('1abc');
    }

    public function testTextTrimsAndTreatsEmptyAsNull(): void
    {
        $field = Text::new('name');
        self::assertSame('Tee', $field->fromInput('  Tee '));
        self::assertNull($field->fromInput('   '));
        self::assertNull($field->fromInput(['array']));
        self::assertSame(['name' => null], $field->toDb(''));
    }

    public function testEmailIsLowercased(): void
    {
        self::assertSame('info@example.org', Email::new('email')->fromInput(' Info@Example.ORG '));
    }

    public function testIntegerKeepsInvalidInputForValidation(): void
    {
        $field = Integer::new('stock');
        self::assertSame(12, $field->fromInput('12'));
        self::assertSame('12a', $field->fromInput('12a'));
        self::assertSame(['stock' => 12], $field->toDb(12));
        self::assertSame(12, $field->fromDb('12'));
    }

    #[DataProvider('provideDecimalInput')]
    public function testDecimalAcceptsGermanNotation(string $input, string $expected): void
    {
        self::assertSame($expected, Decimal::new('price')->fromInput($input));
    }

    /** @return iterable<array{string, string}> */
    public static function provideDecimalInput(): iterable
    {
        yield ['12,50', '12.50'];
        yield ['1.234,56', '1234.56'];
        yield ['1234.5', '1234.5'];
        yield [' 3 ', '3'];
        // a value rendered back from the database must survive another submission
        yield ['0.125', '0.125'];
        yield ['1.250', '1.250'];
    }

    public function testDecimalColumnUsesPrecision(): void
    {
        $column = Decimal::new('price')->precision(8, 3)->columns()[0];
        self::assertSame('decimal(8,3)', $column->type);
    }

    public function testCheckboxIsNeverNull(): void
    {
        $field = Checkbox::new('active');
        self::assertFalse($field->fromInput('0'));
        self::assertTrue($field->fromInput('1'));
        self::assertFalse($field->fromDb(null));
        self::assertSame(['active' => 0], $field->toDb(false));
        self::assertFalse($field->columns()[0]->nullable);
    }

    public function testDateParsesStrictly(): void
    {
        $field = Date::new('day');
        $date = $field->fromInput('2026-02-28');
        self::assertInstanceOf(DateTimeImmutable::class, $date);
        self::assertSame('2026-02-28', $date->format('Y-m-d'));

        // impossible dates stay strings so validation reports them
        self::assertSame('2026-02-31', $field->fromInput('2026-02-31'));
        self::assertSame('tomorrow', $field->fromInput('tomorrow'));
        self::assertSame(['day' => '2026-02-28'], $field->toDb($date));
        self::assertNull($field->fromDb('0000-00-00'));
    }

    public function testDateTimeAcceptsSeconds(): void
    {
        $value = DateTime::new('at')->fromInput('2026-09-28T10:15:30');
        self::assertInstanceOf(DateTimeImmutable::class, $value);
        self::assertSame('2026-09-28 10:15:00', DateTime::new('at')->toDb($value)['at']);
    }

    public function testTimeNormalisesToMinutes(): void
    {
        $field = Time::new('start');
        self::assertSame('09:30', $field->fromInput('09:30:00'));
        self::assertSame(['start' => '09:30:00'], $field->toDb('09:30'));
        self::assertSame('09:30', $field->fromDb('09:30:00'));
    }

    public function testChoiceWithEnumOptionsAndDefault(): void
    {
        $field = Choice::new('colour')->options(Colour::class)->default(Colour::Blue);
        self::assertSame(['red' => 'Farbe Red', 'blue' => 'Farbe Blue'], $field->getOptions());
        self::assertSame('blue', $field->defaultValue());
        self::assertSame('blue', $field->columns()[0]->default);
    }

    public function testMultipleChoiceStoresJson(): void
    {
        $field = Choice::new('tags')->options(['a' => 'A', 'b' => 'B'])->multiple();
        self::assertSame(['a', 'b'], $field->fromInput(['a', '', 'b']));
        self::assertSame(['tags' => '["a","b"]'], $field->toDb(['a', 'b']));
        self::assertSame(['a', 'b'], $field->fromDb('["a","b"]'));
        self::assertSame([], $field->fromDb(null));
        self::assertSame(['tags' => null], $field->toDb([]));
        self::assertSame('text', $field->columns()[0]->type);
    }

    public function testChoiceGroupsAreFlattened(): void
    {
        $field = Choice::new('fruit')->options(['Obst' => ['apple' => 'Apfel'], 'nut' => 'Nuss']);
        self::assertSame(['apple' => 'Apfel', 'nut' => 'Nuss'], $field->flatOptions());
    }

    public function testUniqueCreatesIndex(): void
    {
        $indexes = Text::new('sku')->unique(['language_id'])->indexes();
        self::assertCount(1, $indexes);
        self::assertSame(['sku', 'language_id'], $indexes[0]->columns);
    }

    public function testUuidFormat(): void
    {
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', Uuid::generate());
    }
}
