<?php

namespace Yakamara\YForm\Attribute;

use Attribute;
use Yakamara\YForm\Form\Definition\FormDefinition;

/**
 * Marks a {@see FormDefinition} subclass as (frontend) form.
 *
 * ```php
 * #[AsForm('contact', label: 'Kontaktformular')]
 * final class ContactForm extends FormDefinition { … }
 *
 * echo Forms::get('contact')->run();   // e.g. in a module or template
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsForm
{
    public function __construct(
        public string $name,
        public ?string $label = null,
    ) {}
}
