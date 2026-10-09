<?php

namespace Yakamara\YForm\Form\Action;

/**
 * Replaces the success message shown instead of the form. The message is trusted HTML; `{field}` placeholders are
 * replaced with the escaped values: `new ShowMessage('<p>Danke, {name}!</p>')`.
 */
final class ShowMessage extends Action
{
    public function __construct(
        public readonly string $message,
    ) {}

    public function execute(Submission $submission): void
    {
        $submission->message = $submission->interpolateHtml($this->message);
    }
}
