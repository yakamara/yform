<?php

namespace Yakamara\YForm\Tests\Data;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yakamara\YForm\Attribute\AsTable;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\CreatedAt;
use Yakamara\YForm\Field\CreatedBy;
use Yakamara\YForm\Field\Password;
use Yakamara\YForm\Field\Text;
use Yakamara\YForm\Field\UpdatedAt;
use Yakamara\YForm\Field\Uuid;
use Yakamara\YForm\Table\Table;

use const PASSWORD_DEFAULT;

/**
 * @internal
 */
final class RecordReplicateTest extends TestCase
{
    public function testCopyTakesOverValuesExceptAutomaticOnes(): void
    {
        $table = new class(new AsTable('rex_test')) extends Table {
            public function fields(): iterable
            {
                yield Text::new('title');
                yield Text::new('slug')->cloneable(false)->default('neu');
                yield Uuid::new('uuid');
                yield Password::new('password');
                yield CreatedAt::create();
                yield UpdatedAt::create();
                yield CreatedBy::create();
            }
        };
        $now = new DateTimeImmutable();
        $source = new Record($table, [
            'title' => 'Tee',
            'slug' => 'tee',
            'uuid' => Uuid::generate(),
            'password' => password_hash('geheim', PASSWORD_DEFAULT),
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => 'admin',
        ], 7);

        $copy = $source->replicate();

        self::assertTrue($copy->isNew());
        self::assertNotSame($source, $copy);
        self::assertSame('Tee', $copy->get('title'));
        self::assertSame('neu', $copy->get('slug'));
        self::assertNull($copy->get('uuid'));
        self::assertNull($copy->get('password'));
        self::assertNull($copy->get('created_at'));
        self::assertNull($copy->get('updated_at'));
        self::assertNull($copy->get('created_by'));
        self::assertSame(7, $source->id);
        self::assertSame('tee', $source->get('slug'));
    }
}
