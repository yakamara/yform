<?php

namespace Yakamara\YForm\Module;

use Override;
use Redaxo\Core\Content\ArticleSlice;
use Redaxo\Core\Content\AsModule;
use Redaxo\Core\Content\Module;
use Redaxo\Core\Core;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Message;
use Yakamara\YForm\Form\Definition\Forms;

use function Redaxo\Core\View\escape;

/**
 * Module shipped by YForm: outputs one of the forms registered with `#[AsForm(module: true)]`.
 *
 * The slice stores only the form name (value 1); fields, rules and actions live in the form class.
 */
#[AsModule('yform_form', 'YForm: Formular')]
final class FormModule extends Module
{
    #[Override]
    public function input(ArticleSlice $slice): string
    {
        $forms = Forms::forModule();
        if ([] === $forms) {
            return Message::info(I18n::msg('yform_module_no_forms'));
        }

        $current = (string) $slice->getValue(1);
        $options = '';
        foreach ($forms as $name => $form) {
            $options .= '<option value="' . escape($name) . '"' . ($name === $current ? ' selected' : '') . '>' . escape($form->label()) . '</option>';
        }

        $warning = '' !== $current && !isset($forms[$current])
            ? Message::warning(I18n::msg('yform_module_form_missing', escape($current)))
            : '';

        return $warning . '<div class="form-group">'
            . '<label for="yform-module-form">' . I18n::msg('yform_form') . '</label>'
            . '<select class="form-control" id="yform-module-form" name="REX_INPUT_VALUE[1]">' . $options . '</select>'
            . '<p class="help-block">' . I18n::msg('yform_module_help') . '</p>'
            . '</div>';
    }

    #[Override]
    public function output(ArticleSlice $slice): string
    {
        $form = Forms::find((string) $slice->getValue(1));

        // The backend shows a placeholder: processing a submission there would run the actions (mails, records).
        if (Core::isBackend()) {
            return null === $form
                ? Message::warning(I18n::msg('yform_module_form_missing', escape((string) $slice->getValue(1))))
                : Message::info(I18n::msg('yform_module_preview', escape($form->label())));
        }

        return null === $form || !$form->definition->module ? '' : $form->run();
    }
}
