<?php

declare(strict_types=1);

namespace Redaxo\YForm\Test;

use Redaxo\YForm\Test\Exception\AssertionFailedException;
use Redaxo\YForm\Test\Exception\TestSkippedException;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Executes a test suite: instantiates the suite class, discovers test*
 * methods, drives the setUp/test/tearDown cycle, collects results.
 *
 * @package redaxo\yform
 * @internal
 */
final class TestRunner
{
    /**
     * @param array{
     *   filter?: string,
     *   bail?: bool,
     *   keep_fixtures?: bool,
     *   prefix?: ?string,
     * } $options
     */
    public function run(string $suiteKey, array $options = []): SuiteResult
    {
        $class = SuiteRegistry::get($suiteKey);

        $filter   = (string) ($options['filter'] ?? '');
        $bail     = (bool)   ($options['bail']  ?? false);
        $keep     = (bool)   ($options['keep_fixtures'] ?? false);
        $prefix   = (string) ($options['prefix'] ?? '') !== ''
            ? (string) $options['prefix']
            : 'unittest_' . substr(uniqid('', true), -8) . '_';

        $fixtures = new FixtureManager($prefix);
        $mailer   = new MailerStub();
        $mailer->activate();

        /** @var AbstractTestSuite $instance */
        $instance = new $class($fixtures, $mailer);

        $reflection = new ReflectionClass($instance);
        $methods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static function (ReflectionMethod $m): bool {
                if (!str_starts_with($m->getName(), 'test')) {
                    return false;
                }
                if ($m->isStatic() || $m->isAbstract() || $m->getNumberOfRequiredParameters() > 0) {
                    return false;
                }
                if ($m->getDeclaringClass()->getName() === AbstractTestSuite::class) {
                    return false;
                }
                return true;
            },
        ));

        if ('' !== $filter) {
            $methods = array_values(array_filter(
                $methods,
                static fn (ReflectionMethod $m): bool => false !== stripos($m->getName(), $filter),
            ));
        }

        $startSuite = microtime(true);
        $results = [];

        try {
            $instance->setUpBeforeClass();
        } catch (Throwable $e) {
            $results[] = TestResult::errored('setUpBeforeClass', 0, $e);
            return new SuiteResult(
                $suiteKey,
                $class,
                $results,
                $prefix,
                $fixtures->leftBehind(),
                (int) ((microtime(true) - $startSuite) * 1000),
            );
        }

        foreach ($methods as $method) {
            $name = $method->getName();
            $startMethod = microtime(true);

            try {
                $instance->setUp();
                $method->invoke($instance);
                $instance->tearDown();
                $results[] = TestResult::passed($name, $this->elapsed($startMethod));
            } catch (TestSkippedException $e) {
                $results[] = TestResult::skipped($name, $e->getMessage());
                $this->tryTearDown($instance);
            } catch (AssertionFailedException $e) {
                $results[] = TestResult::failed($name, $this->elapsed($startMethod), $e);
                $this->tryTearDown($instance);
                if ($bail) {
                    break;
                }
            } catch (Throwable $e) {
                $results[] = TestResult::errored($name, $this->elapsed($startMethod), $e);
                $this->tryTearDown($instance);
                if ($bail) {
                    break;
                }
            }
        }

        try {
            $instance->tearDownAfterClass();
        } catch (Throwable $e) {
            $results[] = TestResult::errored('tearDownAfterClass', 0, $e);
        }

        if (!$keep) {
            $fixtures->cleanup();
        }

        return new SuiteResult(
            $suiteKey,
            $class,
            $results,
            $prefix,
            $fixtures->leftBehind(),
            (int) ((microtime(true) - $startSuite) * 1000),
        );
    }

    private function elapsed(float $startMicrotime): int
    {
        return (int) ((microtime(true) - $startMicrotime) * 1000);
    }

    private function tryTearDown(AbstractTestSuite $instance): void
    {
        try {
            $instance->tearDown();
        } catch (Throwable) {
            // Teardown errors after a test failure shouldn't mask the real failure.
        }
    }
}
