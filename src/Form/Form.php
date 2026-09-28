<?php

namespace Yakamara\YForm\Form;

use Closure;
use InvalidArgumentException;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Form\Layout\Layout;
use Yakamara\YForm\Form\Layout\Layouts;
use Yakamara\YForm\Form\Theme\Theme;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Validation\Errors;
use Yakamara\YForm\Validation\ValidationContext;
use Yakamara\YForm\Validation\ValidationException;
use Yakamara\YForm\View\ViewRenderer;

use function array_key_exists;
use function assert;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

/**
 * A rendered form over a set of fields — either bound to a table record (backend editing, frontend forms that
 * write into a table) or free (contact form).
 *
 * ```php
 * $form = Form::forRecord(ProductTable::create());
 * if ($form->handleRequest()) {
 *     // saved
 * }
 * echo $form->render();
 * ```
 */
final class Form
{
    /** @var array<string, Field> */
    public readonly array $fields;

    /** @var array<string, mixed> */
    public private(set) array $values = [];

    public private(set) Errors $errors;

    public private(set) bool $submitted = false;

    /** Name of the clicked submit button (`save`, `apply`, … as passed to {@see addButton()}). */
    public private(set) ?string $action = null;

    public readonly Layout $layout;

    public ?string $actionUrl = null;
    public string $method = 'post';
    public bool $csrf = true;

    /** Renders all fields read-only and ignores submissions. */
    public bool $readonly = false {
        set {
            $this->readonly = $value;
            if ($value) {
                $this->buttons = [];
            }
        }
    }

    /** @var array<string, array{label: string, primary: bool}> */
    public private(set) array $buttons = [];

    /** @var array<string, string|int> */
    public private(set) array $hiddenFields = [];

    /** @var list<Closure(array<string, mixed>, Errors): void> */
    private array $validators = [];

    public ?Table $table {
        get => $this->record?->table;
    }

    /** @param iterable<Field> $fields */
    public function __construct(
        public readonly string $name,
        iterable $fields,
        ?Layout $layout = null,
        public readonly Theme $theme = new Theme(),
        public readonly ?Record $record = null,
    ) {
        $list = [];
        foreach ($fields as $key => $field) {
            $list[$field->name] = $field;
        }
        $this->fields = $list;
        $this->layout = ($layout ?? new Layout())->resolve($list);
        $this->errors = new Errors();

        foreach ($list as $name => $field) {
            $this->values[$name] = null !== $record && $record->has($name) ? $record->get($name) : $field->defaultValue();
        }

        $this->addButton('save', I18n::msg('yform_save'), primary: true);
    }

    /** Form over all fields of the record's table, arranged by the table's layout. */
    public static function forRecord(Record $record, ?Theme $theme = null, ?string $name = null): self
    {
        $table = $record->table;
        return new self($name ?? 'yform-' . $table->name, $table->getFields(), Layouts::forTable($table), $theme ?? Theme::bootstrap3(), $record);
    }

    // ---------------------------------------------------------------- configuration

    public function addButton(string $action, string $label, bool $primary = false): self
    {
        $this->buttons[$action] = ['label' => $label, 'primary' => $primary];
        return $this;
    }

    public function removeButton(string $action): self
    {
        unset($this->buttons[$action]);
        return $this;
    }

    public function addHiddenField(string $name, string|int $value): self
    {
        $this->hiddenFields[$name] = $value;
        return $this;
    }

    /** Form-level validation (in addition to the field rules): `fn (array $values, Errors $errors) => …`. */
    public function addValidator(Closure $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    /** @param array<string, mixed> $values */
    public function setValues(array $values): self
    {
        foreach ($values as $name => $value) {
            if (!isset($this->fields[$name])) {
                throw new InvalidArgumentException(sprintf('Form "%s" has no field "%s".', $this->name, $name));
            }
            $this->values[$name] = $value;
        }
        return $this;
    }

    public function value(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    // ---------------------------------------------------------------- processing

    /**
     * Reads a submission of this form, validates it and — for record forms — saves the record.
     *
     * @return bool `true` when the form was submitted and processed successfully
     */
    public function handleRequest(): bool
    {
        $data = 'get' === $this->method ? Request::get($this->name, 'array', []) : Request::post($this->name, 'array', []);
        if ($this->readonly || !is_array($data) || !array_key_exists('_yform', $data)) {
            return false;
        }

        $this->submitted = true;
        $action = $data['_action'] ?? null;
        $this->action = is_string($action) && isset($this->buttons[$action]) ? $action : array_key_first($this->buttons);

        if ($this->csrf && !$this->csrfToken()->isValid()) {
            $this->errors->addGlobal(I18n::msg('csrf_token_invalid'));
            return false;
        }

        foreach ($this->fields as $name => $field) {
            if (!$this->isEditable($field)) {
                continue;
            }
            $value = $field->fromInput($data[$name] ?? null);
            if ($field->isEmpty($value) && $field->keepsValueOnEmptyInput() && !$field->isEmpty($this->values[$name])) {
                continue;
            }
            $this->values[$name] = $value;
        }

        return null !== $this->record ? $this->saveRecord() : $this->validate();
    }

    public function isValid(): bool
    {
        return $this->submitted && $this->errors->isEmpty();
    }

    private function validate(): bool
    {
        $context = new ValidationContext($this->values);
        foreach ($this->fields as $name => $field) {
            if (!$field->storesValue() || !$this->isEditable($field)) {
                continue;
            }
            foreach ($field->validate($this->values[$name], $context) as $message) {
                $this->errors->add($name, $message);
            }
        }
        foreach ($this->validators as $validator) {
            $validator($this->values, $this->errors);
        }
        return $this->errors->isEmpty();
    }

    private function saveRecord(): bool
    {
        assert(null !== $this->record);

        $formErrors = new Errors();
        foreach ($this->validators as $validator) {
            $validator($this->values, $formErrors);
        }

        foreach ($this->fields as $name => $field) {
            if ($field->storesValue() && $this->record->has($name) && $this->isEditable($field)) {
                $this->record->set($name, $this->values[$name]);
            }
        }

        if (!$formErrors->isEmpty()) {
            $this->errors = $formErrors;
            return false;
        }

        try {
            $this->record->save();
        } catch (ValidationException $e) {
            $this->errors = $e->errors;
            return false;
        }

        // Reflect values set while saving (timestamps, hashes, …).
        foreach ($this->fields as $name => $field) {
            if ($this->record->has($name)) {
                $this->values[$name] = $this->record->get($name);
            }
        }
        return true;
    }

    public function isEditable(Field $field): bool
    {
        return !$this->readonly && $field->isEditable();
    }

    public function csrfToken(): CsrfToken
    {
        return CsrfToken::factory('yform_' . $this->name);
    }

    // ---------------------------------------------------------------- rendering

    public function render(): string
    {
        return ViewRenderer::render(self::class, $this);
    }

    public function fieldContext(Field $field): FieldContext
    {
        return new FieldContext($this->name, $field, $this->values[$field->name] ?? null, $this->theme, $this->errors->for($field->name), $this->record);
    }

    /** Renders one field including label, notice and errors (or its static value when not editable). */
    public function renderField(string $name): string
    {
        $field = $this->fields[$name] ?? throw new InvalidArgumentException(sprintf('Form "%s" has no field "%s".', $this->name, $name));
        return ViewRenderer::render(FieldGroup::class, $field, $this->fieldContext($field), $this->isEditable($field));
    }

    /** Whether the field has something to show (a non-editable field without value is hidden). */
    public function isVisible(string $name): bool
    {
        $field = $this->fields[$name];
        return $this->isEditable($field) || !$field->storesValue() || !$field->isEmpty($this->values[$name] ?? null);
    }

    /** Index of the tab to open initially: the first one with errors. */
    public function activeTab(): int
    {
        foreach ($this->layout->tabs as $index => $tab) {
            foreach ($tab->sections as $section) {
                foreach ($section->rows as $row) {
                    foreach ($row->cells as $cell) {
                        if ($this->errors->has($cell->field)) {
                            return $index;
                        }
                    }
                }
            }
        }
        return 0;
    }

    /** @return list<string> errors not bound to a visible field */
    public function globalErrors(): array
    {
        $messages = $this->errors->for('');
        foreach ($this->errors->toArray() as $field => $fieldMessages) {
            if ('' !== $field && (!isset($this->fields[$field]) || !in_array($field, $this->layout->fieldNames(), true))) {
                foreach ($fieldMessages as $message) {
                    $messages[] = $message;
                }
            }
        }
        return $messages;
    }
}
