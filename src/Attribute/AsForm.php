<?php

namespace Yakamara\YForm\Attribute;

use Attribute;
use Yakamara\YForm\Form\Definition\FormDefinition;

/**
 * Marks a {@see FormDefinition} subclass as (frontend) form.
 *
 * ```php
 * #[AsForm('contact', label: 'Kontaktformular', module: true)]
 * final class ContactForm extends FormDefinition { … }
 *
 * echo Forms::get('contact')->run();   // anywhere in a template or module
 * ```
 *
 * With `module: true` the form is offered in the module „YForm: Formular“ shipped by the addon, so editors can
 * place it on any article. Forms without the flag (e.g. internal login forms) stay out of that list.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsForm
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public bool $module = false,
    ) {}
}
