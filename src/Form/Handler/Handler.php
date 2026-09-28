<?php

namespace Yakamara\YForm\Form\Handler;

use Yakamara\YForm\Form\Definition\FormDefinition;

/**
 * A step executed after a valid submission of a {@see FormDefinition}
 * (the replacement for the former actions). Throw to abort; the form is shown again with an error.
 */
interface Handler
{
    public function handle(Submission $submission): void;
}
