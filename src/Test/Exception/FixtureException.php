<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test\Exception;

use RuntimeException;

/**
 * Thrown by FixtureManager when a fixture cannot be created or loaded.
 *
 * @package redaxo\yform
 * @internal
 */
final class FixtureException extends RuntimeException {}
