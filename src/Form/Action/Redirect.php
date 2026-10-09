<?php

namespace Yakamara\YForm\Form\Action;

use Redaxo\Core\Filesystem\Url;

use function is_int;

/**
 * Redirects after all actions ran: to an article id or a URL (with `{field}`/`{id}` placeholders).
 * The last redirect wins.
 */
final class Redirect extends Action
{
    public function __construct(
        public readonly int|string $target,
    ) {}

    public function execute(Submission $submission): void
    {
        $submission->redirectUrl = is_int($this->target) ? Url::article($this->target) : $submission->interpolate($this->target);
    }
}
