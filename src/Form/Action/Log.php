<?php

namespace Yakamara\YForm\Form\Action;

use Redaxo\Core\Log\Logger;

/**
 * Writes a line to the system log (System → Log), e.g. `new Log('Kontaktanfrage von {email}')`.
 */
final class Log extends Action
{
    public function __construct(
        public readonly string $message,
        public readonly string $level = 'info',
    ) {}

    public function execute(Submission $submission): void
    {
        Logger::factory()->log($this->level, '[yform:' . $submission->definition->definition->name . '] ' . $submission->interpolate($this->message));
    }
}
