<?php

namespace Yakamara\YForm\Tests\Form;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Form\Action\Action;
use Yakamara\YForm\Form\Action\Submission;
use Yakamara\YForm\Form\Definition\FormDefinition;

/**
 * @internal
 */
final class ActionRunTest extends TestCase
{
    /** @var list<string> */
    private array $log = [];

    public function testFailureBeforeStoringAborts(): void
    {
        $submission = $this->submission();

        $this->expectException(RuntimeException::class);
        FormDefinition::runActions([$this->failing('mail'), $this->ok('redirect')], $submission, fn () => $this->log[] = 'reported');
    }

    public function testFailureAfterStoringKeepsSubmission(): void
    {
        $submission = $this->submission();
        $record = new ReflectionClass(Record::class)->newInstanceWithoutConstructor();

        FormDefinition::runActions([
            $this->ok('save', static fn (Submission $s) => $s->record = $record),
            $this->failing('mail'),
            $this->ok('confirmation'),
        ], $submission, fn () => $this->log[] = 'reported');

        self::assertSame(['save', 'mail', 'reported', 'confirmation'], $this->log);
        self::assertCount(1, $submission->failures);
    }

    public function testOptionalActionNeverAborts(): void
    {
        $submission = $this->submission();

        FormDefinition::runActions([$this->failing('mail')->optional(), $this->ok('redirect')], $submission, fn () => $this->log[] = 'reported');

        self::assertSame(['mail', 'reported', 'redirect'], $this->log);
        self::assertCount(1, $submission->failures);
    }

    private function submission(): Submission
    {
        return new ReflectionClass(Submission::class)->newInstanceWithoutConstructor();
    }

    /** @param (Closure(Submission): mixed)|null $effect */
    private function ok(string $name, ?Closure $effect = null): Action
    {
        return new class($name, $effect, $this->log) extends Action {
            /** @param list<string> $log */
            public function __construct(
                private readonly string $name,
                private readonly ?Closure $effect,
                private array &$log,
            ) {}

            public function execute(Submission $submission): void
            {
                $this->log[] = $this->name;
                if ($this->effect) {
                    ($this->effect)($submission);
                }
            }
        };
    }

    private function failing(string $name): Action
    {
        return new class($name, $this->log) extends Action {
            /** @param list<string> $log */
            public function __construct(
                private readonly string $name,
                private array &$log,
            ) {}

            public function execute(Submission $submission): void
            {
                $this->log[] = $this->name;
                throw new RuntimeException($this->name . ' failed');
            }
        };
    }
}
