<?php

namespace Yakamara\YForm\Form\Action;

use Closure;

/** Inline action: `new Callback(fn (Submission $s) => Newsletter::subscribe($s->value('email')))`. */
final class Callback extends Action
{
    /** @param Closure(Submission): void $callback */
    public function __construct(
        public readonly Closure $callback,
    ) {}

    public function execute(Submission $submission): void
    {
        ($this->callback)($submission);
    }
}
