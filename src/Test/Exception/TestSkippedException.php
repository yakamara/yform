<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test\Exception;

use RuntimeException;

/**
 * Thrown by AbstractTestSuite::markSkipped() to abort the current test method
 * without flagging it as a failure.
 *
 * @package redaxo\yform
 * @internal
 */
final class TestSkippedException extends RuntimeException {}
