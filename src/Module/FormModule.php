<?php

namespace Yakamara\YForm\Module;

use Override;
use Redaxo\Core\Content\AsModule;
use Redaxo\Core\Content\Module;
use Redaxo\Core\Core;
use Redaxo\Core\Form\Field\Choice;
use Redaxo\Core\Form\Form;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\Translation\Translatable;
use Redaxo\Core\View\Message;
use Yakamara\YForm\Form\Definition\Forms;

use function Redaxo\Core\View\escape;

/**
 * Module shipped by YForm: outputs one of the forms registered with `#[AsForm(module: true)]`.
 *
 * The slice stores only the form name; fields, rules and actions live in the form class.
 */
#[AsModule('yform_form', 'YForm: Formular')]
final class FormModule extends Module
{
    #[Choice(new Translatable('yform_form'), choices: self::formChoices(...), note: new Translatable('yform_module_help'), required: true)]
    public ?string $form = null;

    /** @return array<string, string> */
    public static function formChoices(): array
    {
        $choices = [];
        foreach (Forms::forModule() as $name => $form) {
            $choices[$name] = $form->label();
        }

        return $choices;
    }

    #[Override]
    public function input(Form $form): string
    {
        $forms = Forms::forModule();
        if ([] === $forms) {
            return Message::info(I18n::msg('yform_module_no_forms'));
        }

        $warning = null !== $this->form && '' !== $this->form && !isset($forms[$this->form])
            ? Message::warning(I18n::msg('yform_module_form_missing', escape($this->form)))
            : '';

        return $warning . $form->rest();
    }

    #[Override]
    public function output(): string
    {
        $name = (string) $this->form;
        $form = Forms::find($name);

        // The backend shows a placeholder: processing a submission there would run the actions (mails, records).
        if (Core::isBackend()) {
            return null === $form
                ? Message::warning(I18n::msg('yform_module_form_missing', escape($name)))
                : Message::info(I18n::msg('yform_module_preview', escape($form->label())));
        }

        return null === $form || !$form->definition->module ? '' : $form->run();
    }
}
