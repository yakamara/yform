<?php

namespace Yakamara\YForm\Field;

use BackedEnum;
use InvalidArgumentException;
use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Index;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Form\FieldContext;
use Yakamara\YForm\Schema\TableSchema;
use Yakamara\YForm\Search\Filter;
use Yakamara\YForm\Search\TextFilter;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Validation\Rule;
use Yakamara\YForm\Validation\Rule\Unique;
use Yakamara\YForm\Validation\ValidationContext;
use Yakamara\YForm\View\ViewRenderer;

use function is_array;
use function is_string;
use function preg_match;
use function Redaxo\Core\View\escape;
use function sprintf;

use const JSON_UNESCAPED_UNICODE;

/**
 * Base class of all field types.
 *
 * The class *is* the field type: there is no registry and no stored definition. A table or form yields
 * configured instances from its `fields()` method; project-specific types are ordinary subclasses.
 *
 * A field covers the whole life cycle of one value:
 *  - storage: {@see columns()}, {@see fromDb()}, {@see toDb()}
 *  - input:   {@see render()}, {@see fromInput()}
 *  - rules:   {@see validate()}
 *  - output:  {@see listValue()}, {@see textValue()}
 *  - search:  {@see searchFilter()}
 *
 * Configuration happens through fluent setters, so the constructor stays identical for all types:
 * `Text::new('title')->label('Titel')->required()`.
 */
abstract class Field
{
    public protected(set) string $label;
    public protected(set) ?string $notice = null;
    public protected(set) bool $required = false;
    public protected(set) mixed $default = null;
    public protected(set) bool $readonly = false;
    public protected(set) bool $listed = true;
    public protected(set) bool $filterable = false;
    public protected(set) bool $sortable = true;
    public protected(set) bool $cloneable = true;

    /** @var list<string>|null columns forming a unique key together with this field, `null` = not unique */
    public protected(set) ?array $unique = null;

    /** @var list<Rule> */
    public protected(set) array $rules = [];

    /** @var array<literal-string, mixed> */
    public protected(set) array $attributes = [];

    final public function __construct(
        public readonly string $name,
    ) {
        if (1 !== preg_match('/^[a-z][a-z0-9_]*$/i', $name)) {
            throw new InvalidArgumentException(sprintf('Invalid field name "%s": use letters, digits and underscores, starting with a letter.', $name));
        }
        $this->label = $name;
        $this->init();
    }

    public static function new(string $name): static
    {
        return new static($name);
    }

    /** Hook for subclasses to set type specific defaults (the constructor is final). */
    protected function init(): void {}

    // ---------------------------------------------------------------- configuration

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function notice(?string $notice): static
    {
        $this->notice = $notice;
        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    /** Initial value of new records; backed enums are stored by their value. */
    public function default(mixed $default): static
    {
        $this->default = $default instanceof BackedEnum ? $default->value : $default;
        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;
        return $this;
    }

    /** Whether the field is shown as a column in the backend list by default. */
    public function listed(bool $listed = true): static
    {
        $this->listed = $listed;
        return $this;
    }

    /** Whether the detailed search of the backend list offers a control for this field ({@see searchFilter()}). */
    public function filterable(bool $filterable = true): static
    {
        $this->filterable = $filterable;
        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    /** Whether a cloned record takes over the value; otherwise the copy starts with the default value. */
    public function cloneable(bool $cloneable = true): static
    {
        $this->cloneable = $cloneable;
        return $this;
    }

    /**
     * Makes the value unique within the table: adds a unique index and the matching validation rule.
     *
     * @param list<string> $scope further columns forming the unique key (e.g. `['clang_id']`)
     */
    public function unique(array $scope = []): static
    {
        $this->unique = $scope;
        $this->rules[] = new Unique($scope);
        return $this;
    }

    public function rule(Rule ...$rules): static
    {
        foreach ($rules as $rule) {
            $this->rules[] = $rule;
        }
        return $this;
    }

    /**
     * Purely presentational attributes for the form control (`placeholder`, `data-*`, …).
     *
     * @param array<literal-string, mixed> $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = [...$this->attributes, ...$attributes];
        return $this;
    }

    /** Label with `translate:key` support. */
    public function translatedLabel(): string
    {
        return I18n::translate($this->label, false);
    }

    // ---------------------------------------------------------------- storage

    /**
     * Whether the field has a value in records and forms. Layout elements like {@see Markup} have none.
     *
     * Fields with a value but without {@see columns()} (e.g. many-to-many relations) load it lazily via
     * {@see loadValue()} and persist it in {@see afterSave()}.
     */
    public function storesValue(): bool
    {
        return true;
    }

    /** Loads the value of a field without own column for an existing record. */
    public function loadValue(Record $record): mixed
    {
        return $this->defaultValue();
    }

    /**
     * The columns backing this field. Most fields have exactly one column named like the field.
     *
     * @return list<Column>
     */
    public function columns(): array
    {
        return [new Column($this->name, $this->columnType(), true, $this->columnDefault())];
    }

    /**
     * Indexes the field needs. The default creates a unique index for {@see Unique()}.
     *
     * @return list<Index>
     */
    public function indexes(): array
    {
        if (null === $this->unique) {
            return [];
        }
        $columns = [];
        foreach ($this->columns() as $column) {
            $columns[] = $column->name;
        }
        return [new Index('unique_' . $this->name, [...$columns, ...$this->unique], Index::UNIQUE)];
    }

    /**
     * Further tables the field needs, e.g. a pivot table. They are created and extended by the schema sync but
     * never dropped automatically.
     *
     * @return list<TableSchema>
     */
    public function additionalTables(Table $table): array
    {
        return [];
    }

    /** MySQL column type, e.g. `varchar(191)`. */
    protected function columnType(): string
    {
        return 'text';
    }

    protected function columnDefault(): ?string
    {
        return null;
    }

    /** Converts the stored DB value into the PHP value used by records and forms. */
    public function fromDb(mixed $raw): mixed
    {
        return null === $raw ? null : (string) $raw;
    }

    /**
     * Converts the PHP value into column values.
     *
     * @return array<string, string|int|float|bool|null>
     */
    public function toDb(mixed $value): array
    {
        return [$this->name => $this->isEmpty($value) ? null : (string) $value];
    }

    /** The value a new record starts with. */
    public function defaultValue(): mixed
    {
        return $this->default;
    }

    public function isEmpty(mixed $value): bool
    {
        return null === $value || '' === $value || [] === $value;
    }

    /** Called right before a record is validated and saved (auto values like timestamps, uuids). */
    public function beforeSave(Record $record, bool $isNew): void {}

    /** Value a clone of `$source` starts with ({@see Record::replicate()}). */
    public function cloneValue(mixed $value, Record $source): mixed
    {
        return $this->cloneable ? $value : $this->defaultValue();
    }

    /** Called inside the save transaction after the row has been written (e.g. pivot tables). */
    public function afterSave(Record $record, bool $isNew): void {}

    /** Called inside the delete transaction before the row is removed. */
    public function beforeDelete(Record $record): void {}

    // ---------------------------------------------------------------- input

    /** Whether the field takes part in forms at all (e.g. `CreatedAt` only shows its value). */
    public function isEditable(): bool
    {
        return !$this->readonly;
    }

    /** Whether an empty submission keeps the stored value instead of clearing it (passwords, uploads). */
    public function keepsValueOnEmptyInput(): bool
    {
        return false;
    }

    /** Normalises the submitted raw value (string, array or null) into the PHP value. */
    public function fromInput(mixed $input): mixed
    {
        if (is_array($input)) {
            return null;
        }
        $input = null === $input ? '' : trim((string) $input);
        return '' === $input ? null : $input;
    }

    /** Renders the form control (without label/notice/error wrapper). */
    public function render(FieldContext $context): string
    {
        return ViewRenderer::field($this, $context);
    }

    /** Whether the theme should wrap the control in a labelled form group. */
    public function wrapped(): bool
    {
        return true;
    }

    /**
     * Attributes of the form control: presentational attributes of the field, theme classes and the structural
     * attributes (`id`, `name`, `required`, …) which can not be overridden.
     *
     * @param array<literal-string, mixed> $attributes
     */
    public function controlAttributes(FieldContext $context, array $attributes = [], bool $themeClass = true): HtmlAttributes
    {
        $classes = [];
        if ($themeClass) {
            $classes[$context->theme->control] = true;
            if ('' !== $context->theme->controlInvalid) {
                $classes[$context->theme->controlInvalid] = $context->hasErrors();
            }
        }

        return new HtmlAttributes($this->attributes)->with([
            'class' => $classes,
            'id' => $context->id,
            'name' => $context->inputName(),
            'required' => $this->required && !$this->readonly,
            'readonly' => $this->readonly,
            ...$attributes,
        ]);
    }

    // ---------------------------------------------------------------- validation

    /**
     * Validates the PHP value. The base implementation checks `required` and runs the attached rules;
     * types add their own format checks by overriding and calling the parent.
     *
     * @return list<string> error messages
     */
    public function validate(mixed $value, ValidationContext $context): array
    {
        if ($this->isEmpty($value)) {
            return $this->required ? [I18n::rawMsg('yform_validation_required', $this->translatedLabel())] : [];
        }

        $errors = [];
        foreach ($this->rules as $rule) {
            $message = $rule->validate($value, $this, $context);
            if (null !== $message) {
                $errors[] = $message;
            }
        }
        return $errors;
    }

    // ---------------------------------------------------------------- output

    /** Escaped HTML for a list cell. */
    public function listValue(mixed $value, Record $record): string
    {
        return escape($this->textValue($value));
    }

    /** Plain text representation (exports, e-mails, record labels). */
    public function textValue(mixed $value): string
    {
        if (null === $value) {
            return '';
        }
        return is_string($value) ? $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // ---------------------------------------------------------------- search

    /** Control of the detailed search, `null` when the type cannot be searched this way. */
    public function searchFilter(): ?Filter
    {
        return new TextFilter($this);
    }
}
