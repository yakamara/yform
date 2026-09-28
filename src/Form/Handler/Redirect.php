<?php

namespace Yakamara\YForm\Form\Handler;

use Redaxo\Core\Filesystem\Url;

use function is_int;

/** Redirects after all handlers ran: to an article id or a URL (with `{field}`/`{id}` placeholders). */
final readonly class Redirect implements Handler
{
    public function __construct(
        public int|string $target,
    ) {}

    public function handle(Submission $submission): void
    {
        $submission->redirectUrl = is_int($this->target) ? Url::article($this->target) : $submission->interpolate($this->target);
    }
}
