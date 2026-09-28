<?php

namespace Yakamara\YForm\Form\Handler;

use Closure;

/** Inline handler: `new Callback(fn (Submission $s) => Newsletter::subscribe($s->value('email')))`. */
final readonly class Callback implements Handler
{
    /** @param Closure(Submission): void $callback */
    public function __construct(
        public Closure $callback,
    ) {}

    public function handle(Submission $submission): void
    {
        ($this->callback)($submission);
    }
}
