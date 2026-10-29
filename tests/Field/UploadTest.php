<?php

namespace Yakamara\YForm\Tests\Field;

use Override;
use PHPUnit\Framework\TestCase;
use Redaxo\Core\Filesystem\DefaultPathProvider;
use Redaxo\Core\Filesystem\Path;
use Yakamara\YForm\Attribute\AsTable;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\Upload;
use Yakamara\YForm\Field\Upload\PendingUpload;
use Yakamara\YForm\Field\Upload\UploadedFile;
use Yakamara\YForm\Field\Upload\UploadStorage;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Validation\ValidationContext;

use function file_put_contents;
use function is_file;
use function sys_get_temp_dir;
use function uniqid;

use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;

/**
 * @internal
 */
final class UploadTest extends TestCase
{
    private string $base;

    #[Override]
    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir() . '/yform-upload-test-' . uniqid() . '/';
        $base = $this->base;
        Path::init(new class($base) extends DefaultPathProvider {
            public function __construct(
                private readonly string $root,
            ) {}

            #[Override]
            public function addonData(string $addon, string $file): string
            {
                return $this->root . $addon . '/' . $file;
            }
        });
    }

    #[Override]
    protected function tearDown(): void
    {
        UploadStorage::deleteDir($this->base);
    }

    public function testUploadWaitsUntilTheRecordIsSaved(): void
    {
        $field = Upload::new('cv');
        $pending = $field->fromInput(['upload' => $this->file('Lebenslauf Müller.PDF')]);

        self::assertInstanceOf(PendingUpload::class, $pending);
        self::assertSame('lebenslauf_mueller.pdf', $pending->name);
        self::assertFileExists($pending->path());
        self::assertSame(['cv' => 'lebenslauf_mueller.pdf'], $field->toDb($pending));

        $record = $this->record($field, $pending, 5);
        $field->afterSave($record, true);

        self::assertSame('lebenslauf_mueller.pdf', $record->get('cv'));
        self::assertFileExists($this->base . 'yform/upload/rex_test/cv/5/lebenslauf_mueller.pdf');
        self::assertDirectoryDoesNotExist(UploadStorage::tmpDir($pending->token));
        self::assertSame([], $field->validate('lebenslauf_mueller.pdf', new ValidationContext([], $record->table, $record)));
    }

    public function testFailedSubmissionKeepsTheUploadByItsToken(): void
    {
        $field = Upload::new('cv');
        $pending = $field->fromInput(['upload' => $this->file('a.pdf')]);
        self::assertInstanceOf(PendingUpload::class, $pending);

        // The form is shown again and submits the token instead of the file.
        $again = $field->fromInput(['files' => [$pending->key()], 'upload' => new UploadedFile('', '', 0, UPLOAD_ERR_NO_FILE)]);

        self::assertInstanceOf(PendingUpload::class, $again);
        self::assertSame($pending->token, $again->token);
        self::assertSame('a.pdf', $again->name);
    }

    public function testNewUploadReplacesAndRemovedFilesAreDeleted(): void
    {
        $field = Upload::new('cv');
        $record = $this->record($field, $field->fromInput(['upload' => $this->file('alt.pdf')]), 7);
        $field->afterSave($record, true);
        $dir = $this->base . 'yform/upload/rex_test/cv/7/';

        $record->set('cv', $field->fromInput(['files' => ['k:alt.pdf'], 'upload' => $this->file('neu.pdf')]));
        $field->afterSave($record, false);

        self::assertSame('neu.pdf', $record->get('cv'));
        self::assertFileExists($dir . 'neu.pdf');
        self::assertFileDoesNotExist($dir . 'alt.pdf');

        $record->set('cv', $field->fromInput(['files' => ['k:neu.pdf'], 'remove' => ['k:neu.pdf']]));
        self::assertNull($record->get('cv'));
        $field->afterSave($record, false);
        self::assertDirectoryDoesNotExist($dir);
    }

    public function testMultipleFilesGetUniqueNames(): void
    {
        $field = Upload::new('docs')->multiple();
        $value = $field->fromInput(['files' => ['k:a.pdf'], 'upload' => [$this->file('a.pdf'), $this->file('A.pdf')]]);

        self::assertCount(3, $value);
        self::assertSame(['docs' => '["a.pdf","a_1.pdf","a_2.pdf"]'], $field->toDb($value));
    }

    public function testTypesAndSizeAreChecked(): void
    {
        $field = Upload::new('cv')->types('pdf')->maxSize('1K');

        $wrongType = $field->fromInput(['upload' => $this->file('setup.exe')]);
        self::assertInstanceOf(PendingUpload::class, $wrongType);
        self::assertTrue($wrongType->isRejected());

        $tooLarge = $field->fromInput(['upload' => $this->file('gross.pdf', 2048)]);
        self::assertInstanceOf(PendingUpload::class, $tooLarge);
        self::assertTrue($tooLarge->isRejected());

        $phpLimit = $field->fromInput(['upload' => new UploadedFile('riesig.pdf', '', 0, UPLOAD_ERR_INI_SIZE)]);
        self::assertInstanceOf(PendingUpload::class, $phpLimit);
        self::assertTrue($phpLimit->isRejected());

        self::assertCount(1, $field->validate($wrongType, new ValidationContext([])));
        self::assertNull($wrongType->previous);
        self::assertSame(['cv' => null], $field->toDb($wrongType));
        self::assertTrue(Upload::new('cv')->maxSize('5M')->maxSize === 5 * 1024 * 1024);
    }

    public function testExecutableExtensionsAreAcceptedWithoutTypeList(): void
    {
        $field = Upload::new('tool');
        $pending = $field->fromInput(['upload' => $this->file('deploy.php')]);

        self::assertInstanceOf(PendingUpload::class, $pending);
        self::assertFalse($pending->isRejected());
        self::assertSame('deploy.php', $pending->name);
    }

    public function testRejectedUploadKeepsThePreviousFile(): void
    {
        $field = Upload::new('cv')->types('pdf');
        $record = $this->record($field, $field->fromInput(['upload' => $this->file('alt.pdf')]), 3);
        $field->afterSave($record, true);

        $value = $field->fromInput(['files' => ['k:alt.pdf'], 'upload' => $this->file('bild.jpg')]);

        self::assertInstanceOf(PendingUpload::class, $value);
        self::assertTrue($value->isRejected());
        self::assertSame('k:alt.pdf', $value->previous);
        self::assertSame('alt.pdf', $field->entryFromKey((string) $value->previous));
    }

    public function testTamperedKeysAreIgnoredOrRejected(): void
    {
        $field = Upload::new('cv')->multiple();

        self::assertSame([], $field->fromInput(['files' => ['k:../../config.yml', 'k:.htaccess', 'p:../../x', 'p:' . str_repeat('a', 32), 'x:a.pdf']]));
        // A posted array is never taken as an uploaded file.
        self::assertSame([], $field->fromInput(['upload' => [['name' => 'a.pdf', 'tmp_name' => '/etc/hosts', 'error' => 0, 'size' => 1]]]));

        // A file name that is not stored with the record can not be kept.
        $record = $this->record(Upload::new('cv'), null, 9);
        self::assertCount(1, Upload::new('cv')->validate('fremd.pdf', new ValidationContext([], $record->table, $record)));
        self::assertCount(1, Upload::new('cv')->validate('fremd.pdf', new ValidationContext([])));
    }

    public function testCloneGetsOwnCopiesOfTheFiles(): void
    {
        $field = Upload::new('cv');
        $source = $this->record($field, $field->fromInput(['upload' => $this->file('a.pdf')]), 4);
        $field->afterSave($source, true);

        $copy = $field->cloneValue($source->get('cv'), $source);

        self::assertInstanceOf(PendingUpload::class, $copy);
        self::assertSame('a.pdf', $copy->name);
        self::assertFileExists($copy->path());
        self::assertFileExists($this->base . 'yform/upload/rex_test/cv/4/a.pdf');
        self::assertNull($field->cloneable(false)->cloneValue('a.pdf', $source));
    }

    public function testDeletingTheRecordRemovesItsFiles(): void
    {
        $field = Upload::new('cv');
        $record = $this->record($field, $field->fromInput(['upload' => $this->file('a.pdf')]), 6);
        $field->afterSave($record, true);

        $field->beforeDelete($record);

        self::assertDirectoryDoesNotExist($this->base . 'yform/upload/rex_test/cv/6/');
    }

    public function testFilesTreeFollowsThePostedStructure(): void
    {
        $files = [
            'name' => ['cv' => ['upload' => 'a.pdf'], 'links' => [0 => ['doc' => ['upload' => ['b.pdf', '']]]]],
            'tmp_name' => ['cv' => ['upload' => '/tmp/php1'], 'links' => [0 => ['doc' => ['upload' => ['/tmp/php2', '']]]]],
            'size' => ['cv' => ['upload' => 10], 'links' => [0 => ['doc' => ['upload' => [20, 0]]]]],
            'error' => ['cv' => ['upload' => 0], 'links' => [0 => ['doc' => ['upload' => [0, UPLOAD_ERR_NO_FILE]]]]],
        ];

        $tree = UploadedFile::tree($files);

        self::assertEquals(new UploadedFile('a.pdf', '/tmp/php1', 10, 0, true), $tree['cv']['upload']);
        self::assertEquals(new UploadedFile('b.pdf', '/tmp/php2', 20, 0, true), $tree['links'][0]['doc']['upload'][0]);
        self::assertTrue($tree['links'][0]['doc']['upload'][1]->isEmpty());
    }

    private function file(string $name, int $size = 100): UploadedFile
    {
        $path = $this->base . 'incoming-' . uniqid() . '.tmp';
        UploadStorage::createDir($this->base);
        file_put_contents($path, str_repeat('x', $size));
        self::assertTrue(is_file($path));
        return new UploadedFile($name, $path, $size);
    }

    private function record(Upload $field, mixed $value, int $id): Record
    {
        $table = new class(new AsTable('rex_test')) extends Table {
            public function fields(): iterable
            {
                return [];
            }
        };
        return new Record($table, [$field->name => $value], $id);
    }
}
