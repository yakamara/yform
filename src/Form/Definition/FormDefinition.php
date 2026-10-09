<?php

namespace Yakamara\YForm\Form\Definition;

use LogicException;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;
use Redaxo\Core\Log\Logger;
use Redaxo\Core\Translation\I18n;
use Throwable;
use Yakamara\YForm\Attribute\AsForm;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Form\Action\Action;
use Yakamara\YForm\Form\Action\Submission;
use Yakamara\YForm\Form\Form;
use Yakamara\YForm\Form\Layout\Layout;
use Yakamara\YForm\Form\Layout\LayoutStore;
use Yakamara\YForm\Form\Theme\Theme;
use Yakamara\YForm\Validation\Errors;
use Yakamara\YForm\Validation\ValidationException;

use function Redaxo\Core\View\escape;
use function sprintf;

/**
 * A form defined as class — the replacement for pipe/PHP notation and objparams.
 *
 * ```php
 * #[AsForm('contact', label: 'Kontakt', module: true)]
 * final class ContactForm extends FormDefinition
 * {
 *     public function fields(): iterable
 *     {
 *         yield Text::new('name')->label('Name')->required();
 *         yield Email::new('email')->label('E-Mail')->required();
 *         yield Textarea::new('message')->label('Nachricht')->required();
 *     }
 *
 *     public function actions(): iterable
 *     {
 *         yield new SaveRecord(ContactTable::class);
 *         yield new SendMail(to: 'info@example.org', subject: 'Neue Anfrage von {name}', replyTo: '{email}');
 *         yield new SendMail(to: '{email}', subject: 'Danke für deine Nachricht');
 *     }
 * }
 * ```
 */
abstract class FormDefinition
{
    /** @var array<string, Field>|null */
    private ?array $fieldCache = null;

    final public function __construct(
        public readonly AsForm $definition,
    ) {}

    /** @return iterable<Field> */
    abstract public function fields(): iterable;

    /** Default layout; a layout saved with the designer takes precedence. */
    public function layout(Layout $layout): Layout
    {
        return $layout;
    }

    /**
     * What happens after a valid submission, in order (see {@see Action}). Each action may be attached several
     * times and restricted with `->when()`.
     *
     * @return iterable<Action>
     */
    public function actions(): iterable
    {
        return [];
    }

    /** Cross-field validation. @param array<string, mixed> $values */
    public function validate(array $values, Errors $errors): void {}

    /** CSS vocabulary; override per form or set {@see Forms::$defaultTheme}. */
    public function theme(): Theme
    {
        return Forms::$defaultTheme ?? Theme::bootstrap5();
    }

    public function submitLabel(): string
    {
        return I18n::msg('yform_submit');
    }

    /** Shown instead of the form after a successful submission (unless an action redirects or sets its own message). */
    public function successMessage(): string
    {
        return I18n::msg('yform_form_success');
    }

    /** Adds an invisible field that bots tend to fill in; submissions with a value are rejected silently. */
    public function honeypot(): bool
    {
        return true;
    }

    public function label(): string
    {
        return I18n::translate($this->definition->label ?? $this->definition->name, false);
    }

    /** @return array<string, Field> */
    final public function getFields(): array
    {
        if (null !== $this->fieldCache) {
            return $this->fieldCache;
        }
        $fields = [];
        foreach ($this->fields() as $field) {
            if (isset($fields[$field->name])) {
                throw new LogicException(sprintf('Form "%s" defines the field "%s" twice.', static::class, $field->name));
            }
            $fields[$field->name] = $field;
        }
        return $this->fieldCache = $fields;
    }

    /** Creates a fresh form object (not yet processed). */
    public function build(): Form
    {
        $form = new Form(
            'yform-' . $this->definition->name,
            $this->getFields(),
            LayoutStore::load(static::class) ?? $this->layout(new Layout()),
            $this->theme(),
        );
        $form->removeButton('save');
        $form->addButton('submit', $this->submitLabel(), primary: true);
        $form->addValidator(fn (array $values, Errors $errors) => $this->validate($values, $errors));
        return $form;
    }

    /** Handles the request and returns the HTML to output: the form (with errors) or the success message. */
    public function run(): string
    {
        $form = $this->build();
        $honeypotName = $form->name . '_website';

        $submitted = $form->handleRequest();
        if ($submitted && $this->honeypot() && '' !== Request::post($honeypotName, 'string', '')) {
            // Pretend success to the bot.
            return $this->renderSuccess($form);
        }

        if ($submitted) {
            $submission = new Submission($this, $form);
            try {
                foreach ($this->actions() as $action) {
                    if ($action->shouldRun($submission)) {
                        $action->execute($submission);
                    }
                }
                Extension::dispatch(new ExtensionPoint('YFORM_FORM_SUBMITTED', $submission, ['form' => $this], true));
            } catch (ValidationException) {
                // An action (e.g. SaveRecord) reported field errors on the form.
                return $this->renderForm($form, $honeypotName);
            } catch (Throwable $e) {
                $form->errors->addGlobal(I18n::rawMsg('yform_form_action_error'));
                Logger::logException($e);
                return $this->renderForm($form, $honeypotName);
            }
            if (null !== $submission->redirectUrl) {
                Response::sendRedirect($submission->redirectUrl);
            }
            return $this->renderSuccess($form, $submission->message);
        }

        return $this->renderForm($form, $honeypotName);
    }

    private function renderForm(Form $form, string $honeypotName): string
    {
        $html = $form->render();
        if ($this->honeypot()) {
            $trap = '<div style="position:absolute;left:-10000px" aria-hidden="true"><label>Website <input type="text" name="' . escape($honeypotName) . '" tabindex="-1" autocomplete="off"></label></div>';
            $html = str_replace('</form>', $trap . '</form>', $html);
        }
        return $html;
    }

    private function renderSuccess(Form $form, ?string $message = null): string
    {
        return '<div class="' . escape($form->theme->alertSuccess) . '">' . ($message ?? $this->successMessage()) . '</div>';
    }
}
