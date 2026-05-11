<?php

declare(strict_types=1);

namespace Redaxo\YForm\Test;

use Countable;
use Redaxo\YForm\Test\Exception\TestSkippedException;
use ReflectionClass;
use rex_yform_manager_table;
use Throwable;

/**
 * Base class for test suites. Subclass and add public test* methods.
 *
 * Lifecycle per method:
 *   setUp() -> testFoo() -> tearDown()
 *
 * Lifecycle per suite:
 *   setUpBeforeClass() -> [per-method cycle, repeated] -> tearDownAfterClass()
 *
 * @package redaxo\yform
 * @internal
 */
abstract class AbstractTestSuite
{
    public function __construct(
        protected readonly FixtureManager $fixtures,
        protected readonly MailerStub $mailer,
    ) {}

    /**
     * Override to run setup once before any test* method.
     */
    public function setUpBeforeClass(): void {}

    /**
     * Override to run teardown once after all test* methods.
     */
    public function tearDownAfterClass(): void {}

    /**
     * Override to run setup before each test* method.
     */
    public function setUp(): void {}

    /**
     * Override to run teardown after each test* method.
     */
    public function tearDown(): void {}

    /** Human-readable name. Override for custom titles. */
    public function getSuiteTitle(): string
    {
        $short = (new ReflectionClass(static::class))->getShortName();
        return preg_replace('/Suite$/', '', $short) ?: $short;
    }

    // ---------- Assertion shortcuts ----------

    protected function assertSame(mixed $expected, mixed $actual, string $msg = ''): void
    {
        Assert::same($expected, $actual, $msg);
    }

    protected function assertNotSame(mixed $unexpected, mixed $actual, string $msg = ''): void
    {
        Assert::notSame($unexpected, $actual, $msg);
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
    {
        Assert::equals($expected, $actual, $msg);
    }

    protected function assertTrue(bool $cond, string $msg = ''): void
    {
        Assert::true($cond, $msg);
    }

    protected function assertFalse(bool $cond, string $msg = ''): void
    {
        Assert::false($cond, $msg);
    }

    protected function assertNull(mixed $value, string $msg = ''): void
    {
        Assert::null($value, $msg);
    }

    protected function assertNotNull(mixed $value, string $msg = ''): void
    {
        Assert::notNull($value, $msg);
    }

    protected function assertCount(int $expected, Countable|array $actual, string $msg = ''): void
    {
        Assert::count($expected, $actual, $msg);
    }

    /**
     * @param class-string $class
     */
    protected function assertInstanceOf(string $class, mixed $actual, string $msg = ''): void
    {
        Assert::instanceOf($class, $actual, $msg);
    }

    /**
     * @param class-string<Throwable> $exceptionClass
     */
    protected function assertThrows(string $exceptionClass, callable $fn, string $msg = ''): void
    {
        Assert::throws($exceptionClass, $fn, $msg);
    }

    protected function assertStringContains(string $needle, string $haystack, string $msg = ''): void
    {
        Assert::stringContains($needle, $haystack, $msg);
    }

    protected function assertArrayHasKey(int|string $key, array $array, string $msg = ''): void
    {
        Assert::arrayHasKey($key, $array, $msg);
    }

    protected function assertArrayNotHasKey(int|string $key, array $array, string $msg = ''): void
    {
        Assert::arrayNotHasKey($key, $array, $msg);
    }

    /**
     * Aborts the current test method as "skipped" (not a failure).
     */
    protected function markSkipped(string $reason): never
    {
        throw new TestSkippedException($reason);
    }

    // ---------- Fixture shortcuts ----------

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    protected function createTestTable(string $shortName, array $fields = [], array $tableOptions = []): rex_yform_manager_table
    {
        return $this->fixtures->createTable($shortName, $fields, $tableOptions);
    }

    protected function loadFixture(string $relativePath): rex_yform_manager_table
    {
        return $this->fixtures->loadFromJson($relativePath);
    }
}
