<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test\Exception;

use RuntimeException;

/**
 * Thrown by Assert::* when a test assertion fails.
 *
 * @package redaxo\yform
 * @internal
 */
final class AssertionFailedException extends RuntimeException {}
