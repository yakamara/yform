<?php

namespace Yakamara\YForm\Tests\Form;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yakamara\YForm\Field\Text;
use Yakamara\YForm\Form\Layout\Cell;
use Yakamara\YForm\Form\Layout\Layout;
use Yakamara\YForm\Form\Layout\Section;
use Yakamara\YForm\Form\Layout\Tab;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class LayoutTest extends TestCase
{
    /** @return array<string, Text> */
    private static function fields(string ...$names): array
    {
        $fields = [];
        foreach ($names as $name) {
            $fields[$name] = Text::new($name);
        }
        return $fields;
    }

    public function testRowDistributesWidthsEvenly(): void
    {
        $layout = new Layout()->row('a', 'b', 'c');

        $cells = $layout->tabs[0]->sections[0]->rows[0]->cells;
        self::assertSame([4, 4, 4], array_map(static fn (Cell $c) => $c->width, $cells));
    }

    public function testResolveAppendsUnplacedFieldsAndDropsUnknownOnes(): void
    {
        $layout = new Layout()
            ->tab('Eins', static fn (Tab $t) => $t->row('a', 'gone'))
            ->tab('Zwei', static fn (Tab $t) => $t->row('b'));

        $resolved = $layout->resolve(self::fields('a', 'b', 'c'));

        self::assertSame(['a', 'c', 'b'], $resolved->fieldNames());
        self::assertSame(['Eins', 'Zwei'], array_map(static fn (Tab $t) => $t->label, $resolved->tabs));
    }

    public function testResolvePlacesEachFieldOnlyOnce(): void
    {
        $resolved = new Layout()->row('a', 'a')->row('a')->resolve(self::fields('a'));

        self::assertSame(['a'], $resolved->fieldNames());
    }

    public function testResolveKeepsEmptySectionsWithLegend(): void
    {
        $layout = new Layout()->section('Leer', static fn (Section $s) => $s->row('gone'))->section(null, static fn (Section $s) => $s->row('a'));

        $resolved = $layout->resolve(self::fields('a'));

        self::assertCount(2, $resolved->tabs[0]->sections);
        self::assertSame('Leer', $resolved->tabs[0]->sections[0]->legend);
    }

    public function testEmptyLayoutResolvesToOneRowPerField(): void
    {
        $resolved = new Layout()->resolve(self::fields('a', 'b'));

        self::assertFalse($resolved->hasTabs());
        self::assertCount(2, $resolved->tabs[0]->sections[0]->rows);
    }

    public function testArrayRoundTrip(): void
    {
        $layout = new Layout()
            ->tab('Allgemein', static fn (Tab $t) => $t->row(Cell::field('a', 8), Cell::field('b', 4)))
            ->tab('Mehr', static fn (Tab $t) => $t->section('Details', static fn (Section $s) => $s->row('c')));

        $copy = Layout::fromArray(json_decode(json_encode($layout->toArray(), JSON_THROW_ON_ERROR), true));

        self::assertSame($layout->toArray(), $copy->toArray());
    }

    public function testFromArrayRejectsInvalidWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Layout::fromArray(['tabs' => [['sections' => [['rows' => [[['field' => 'a', 'width' => 13]]]]]]]]);
    }

    public function testFromArrayRejectsMissingTabs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Layout::fromArray(['foo' => []]);
    }
}
