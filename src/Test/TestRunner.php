<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test;

use Redaxo\Core\Core;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use Yakamara\YForm\Test\Exception\AssertionFailedException;
use Yakamara\YForm\Test\Exception\TestSkippedException;

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

        $filter = (string) ($options['filter'] ?? '');
        $bail = (bool) ($options['bail'] ?? false);
        $keep = (bool) ($options['keep_fixtures'] ?? false);
        $prefix = '' !== (string) ($options['prefix'] ?? '')
            ? (string) $options['prefix']
            : 'unittest_' . substr(uniqid('', true), -8) . '_';

        self::provideRequest();

        $fixtures = new FixtureManager($prefix);
        $mailer = new MailerStub();
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
                if (AbstractTestSuite::class === $m->getDeclaringClass()->getName()) {
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

    /**
     * Gives the CLI a request object.
     *
     * Anything that renders a full yform form eventually reaches CsrfToken, which
     * asks Core::getRequest() whether the connection is HTTPS — and that throws
     * outright under the console. Suites can switch csrf_protection off per form,
     * but not for the nested forms yform builds itself (inline relations, for one).
     * A synthetic request is closer to what the code under test normally sees.
     */
    private static function provideRequest(): void
    {
        if (null !== Core::getProperty('request')) {
            return;
        }

        if (PHP_SESSION_NONE === session_status()) {
            @session_start();
        }

        Core::setProperty('request', Request::create('https://yform.test/redaxo/index.php'));
        $_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
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
