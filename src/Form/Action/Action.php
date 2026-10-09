<?php

namespace Yakamara\YForm\Form\Action;

use Closure;
use Yakamara\YForm\Form\Definition\FormDefinition;

/**
 * A step executed after a valid submission of a {@see FormDefinition} — the
 * successor of the YForm 5 actions. A form lists its actions in `actions()`; they run in that order, and the same
 * action type may appear several times (e.g. two mails).
 *
 * ```php
 * public function actions(): iterable
 * {
 *     yield new SaveRecord(ContactTable::class);
 *     yield new SendMail(to: 'info@example.org', subject: 'Neue Anfrage von {name}');
 *     yield new SendMail(to: '{email}', subject: 'Danke!', template: __DIR__ . '/mail/confirmation.php')
 *         ->when(fn (Submission $s) => $s->value('copy'));
 * }
 * ```
 *
 * Throwing aborts the remaining actions; the form is shown again with an error message.
 */
abstract class Action
{
    /** @var (Closure(Submission): bool)|null */
    private ?Closure $condition = null;

    abstract public function execute(Submission $submission): void;

    /** Runs the action only if the condition returns `true`. */
    public function when(Closure $condition): static
    {
        $this->condition = $condition;
        return $this;
    }

    public function shouldRun(Submission $submission): bool
    {
        return null === $this->condition || (bool) ($this->condition)($submission);
    }
}
