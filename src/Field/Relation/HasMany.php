<?php

namespace Yakamara\YForm\Field\Relation;

use LogicException;
use Override;
use Redaxo\Core\Core;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Collection;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Form\FieldContext;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;
use Yakamara\YForm\Validation\ValidationContext;
use Yakamara\YForm\View\ViewRenderer;

use function array_filter;
use function array_key_exists;
use function array_map;
use function count;
use function filter_var;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function Redaxo\Core\View\escape;
use function sprintf;

use const FILTER_VALIDATE_INT;

/**
 * Inverse side of a {@see BelongsTo}: the records of another table pointing to this one.
 *
 * By default read-only (list of links in the backend form). With `->inline()` the related records are edited
 * directly in the form of the parent record — added, changed and removed — and saved in the same transaction.
 *
 * ```php
 * yield HasMany::new('items')->label('Positionen')->target(OrderItemTable::class, 'order_id')->inline();
 * ```
 *
 * The value is the list of ids; while an inline form is processed it is the list of submitted rows
 * (`list<array{id: ?int, values: array<string, mixed>}>`).
 */
class HasMany extends Field implements Relation
{
    /** @var class-string<Table>|string */
    public protected(set) string $target = '';
    public protected(set) string $foreignKey = '';
    public protected(set) bool $inline = false;

    #[Override]
    protected function init(): void
    {
        $this->listed = false;
        $this->sortable = false;
    }

    /** @param class-string<Table>|string $table */
    public function target(string $table, string $foreignKey): static
    {
        $this->target = $table;
        $this->foreignKey = $foreignKey;
        return $this;
    }

    /** Edit the related records inside the form of the parent record. */
    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;
        return $this;
    }

    public function targetTable(): Table
    {
        if ('' === $this->target) {
            throw new LogicException(sprintf('Relation field "%s" has no target table; call ->target().', $this->name));
        }
        return Tables::get($this->target);
    }

    /**
     * Fields of the target table that are edited inline: all editable ones except the back reference and
     * nested has-many relations.
     *
     * @return array<string, Field>
     */
    public function inlineFields(): array
    {
        $fields = [];
        foreach ($this->targetTable()->getFields() as $name => $field) {
            if ($name === $this->foreignKey || $field instanceof self || ($field->storesValue() && !$field->isEditable())) {
                continue;
            }
            $fields[$name] = $field;
        }
        return $fields;
    }

    #[Override]
    public function columns(): array
    {
        return [];
    }

    #[Override]
    public function isEditable(): bool
    {
        return $this->inline && !$this->readonly;
    }

    #[Override]
    public function defaultValue(): mixed
    {
        return [];
    }

    #[Override]
    public function loadValue(Record $record): mixed
    {
        return null === $record->id ? [] : $this->query($record)->ids();
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        return [];
    }

    #[Override]
    public function isEmpty(mixed $value): bool
    {
        return [] === $value || null === $value;
    }

    // ---------------------------------------------------------------- inline editing

    /**
     * Normalises the submitted rows. Rows marked for deletion are dropped, which removes their record on save.
     *
     * @return list<array{id: ?int, values: array<string, mixed>}>
     */
    #[Override]
    public function fromInput(mixed $input): mixed
    {
        if (!$this->inline || !is_array($input)) {
            return [];
        }
        $fields = $this->inlineFields();
        $rows = [];
        foreach ($input as $key => $raw) {
            if ('__index__' === $key || !is_array($raw) || !empty($raw['_delete'])) {
                continue;
            }
            $id = filter_var($raw['_id'] ?? null, FILTER_VALIDATE_INT);
            $values = [];
            foreach ($fields as $name => $field) {
                if ($field->storesValue()) {
                    $values[$name] = $field->fromInput($raw[$name] ?? null);
                }
            }
            $rows[] = ['id' => false === $id ? null : $id, 'values' => $values];
        }
        return $rows;
    }

    /**
     * Rows for the inline editor: from submitted rows (after a failed submission) or loaded from the records.
     *
     * @return list<array{id: ?int, values: array<string, mixed>}>
     */
    public function rows(mixed $value, ?Record $parent): array
    {
        if (self::isRowList($value)) {
            return $value;
        }
        if (null === $parent?->id) {
            return [];
        }
        $rows = [];
        foreach ($this->query($parent)->orderBy('id')->find() as $record) {
            $values = [];
            foreach ($this->inlineFields() as $name => $field) {
                if ($field->storesValue()) {
                    $values[$name] = $record->get($name);
                }
            }
            $rows[] = ['id' => $record->id, 'values' => $values];
        }
        return $rows;
    }

    private static function isRowList(mixed $value): bool
    {
        return is_array($value) && [] !== $value && is_array($value[0] ?? null);
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        if (!self::isRowList($value)) {
            return $this->required && $this->isEmpty($value) ? [I18n::rawMsg('yform_validation_required', $this->translatedLabel())] : [];
        }

        $target = $this->targetTable();
        $errors = [];
        foreach ($value as $index => $row) {
            $record = $this->existingRecord($row['id'], $context->record) ?? $target->newRecord();
            foreach ($row['values'] as $name => $fieldValue) {
                $record->set($name, $fieldValue);
            }
            $rowContext = new ValidationContext($row['values'], $target, $record);
            foreach ($this->inlineFields() as $name => $field) {
                if (!$field->storesValue()) {
                    continue;
                }
                foreach ($field->validate($row['values'][$name] ?? null, $rowContext) as $message) {
                    $errors[] = I18n::rawMsg('yform_inline_row_error', $index + 1, $message);
                }
            }
        }
        return $errors;
    }

    #[Override]
    public function afterSave(Record $record, bool $isNew): void
    {
        if (!$this->inline || !$record->isLoaded($this->name)) {
            return;
        }
        $value = $record->get($this->name);
        if (!self::isRowList($value) && [] !== $value) {
            return; // unchanged id list
        }

        $target = $this->targetTable();
        $keep = [];
        foreach ((array) $value as $row) {
            $child = $this->existingRecord($row['id'], $record) ?? $target->newRecord();
            foreach ($row['values'] as $name => $fieldValue) {
                $child->set($name, $fieldValue);
            }
            $child->set($this->foreignKey, $record->id);
            $child->save();
            $keep[] = (int) $child->id;
        }

        // Records that are no longer part of the submission have been removed in the form.
        foreach ($this->query($record)->find() as $child) {
            if (!in_array((int) $child->id, $keep, true)) {
                $child->delete();
            }
        }

        $record->set($this->name, $keep);
    }

    /** A related record of the parent; ids of foreign records are ignored (tampered input). */
    private function existingRecord(?int $id, ?Record $parent): ?Record
    {
        if (null === $id || null === $parent?->id) {
            return null;
        }
        return $this->query($parent)->where('id', $id)->first();
    }

    // ---------------------------------------------------------------- read access and output

    private function query(Record $record): Query
    {
        return $this->targetTable()->query()->where($this->foreignKey, $record->id);
    }

    /** @return Collection<Record> */
    public function related(Record $record): Collection
    {
        return null === $record->id ? new Collection() : $this->query($record)->find();
    }

    /** @return list<int> */
    private static function ids(mixed $value): array
    {
        if (self::isRowList($value)) {
            return [];
        }
        return array_values(array_map('intval', array_filter((array) $value, is_int(...))));
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        $ids = self::ids($value);
        return [] === $ids ? '' : implode(', ', Labels::get($this->targetTable(), $ids));
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return $this->inline ? (string) count(self::ids($value)) : escape($this->textValue($value));
    }

    #[Override]
    public function applySearch(Query $query, string $term): void {}

    #[Override]
    public function render(FieldContext $context): string
    {
        if ($this->inline) {
            return ViewRenderer::render(self::class, $this, $context, $this->rows($context->value, $context->record));
        }

        $target = $this->targetTable();
        $ids = self::ids($context->value);
        if ([] === $ids) {
            return '<p class="' . escape($context->theme->staticControl) . '">–</p>';
        }
        $items = '';
        foreach (Labels::get($target, $ids) as $id => $label) {
            $items .= Core::isBackend()
                ? '<li><a href="' . escape(Url::backendPage('yform/data', ['table' => $target->name, 'func' => 'edit', 'id' => $id])) . '">' . escape($label) . '</a></li>'
                : '<li>' . escape($label) . '</li>';
        }
        return '<ul class="yform-has-many">' . $items . '</ul>';
    }

    /** Context for a sub field of one inline row; `$index` may be the template placeholder `__index__`. */
    public function rowContext(FieldContext $parent, Field $field, int|string $index, mixed $value): FieldContext
    {
        return new FieldContext($parent->formName . '[' . $this->name . '][' . $index . ']', $field, $value, $parent->theme);
    }

    /** @param array<string, mixed> $values */
    public static function rowValue(array $values, string $name, Field $field): mixed
    {
        return array_key_exists($name, $values) ? $values[$name] : $field->defaultValue();
    }
}
