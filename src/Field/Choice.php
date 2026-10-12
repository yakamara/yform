<?php

namespace Yakamara\YForm\Field;

use BackedEnum;
use Closure;
use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Search\ChoiceFilter;
use Yakamara\YForm\Search\Filter;
use Yakamara\YForm\Validation\ValidationContext;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function method_exists;

use const JSON_UNESCAPED_UNICODE;

/**
 * Choice among options, rendered as select, multi-select, radios or checkboxes (`expanded` × `multiple`).
 *
 * Options are given as `value => label` array (nested arrays become groups), as backed enum class (labels from an
 * optional `label()` method, otherwise the case name) or as closure returning such an array.
 *
 * Single values are stored in a `varchar`, multiple values as JSON array in a `text` column
 * (query with {@see Query::whereJsonContains()}).
 */
class Choice extends Field
{
    public protected(set) bool $multiple = false;
    public protected(set) bool $expanded = false;
    public protected(set) ?string $placeholder = null;

    /** @var array<int|string, string|array<int|string, string>>|class-string<BackedEnum>|Closure(): array<int|string, string|array<int|string, string>> */
    private array|string|Closure $optionSource = [];

    /** @var array<int|string, string|array<int|string, string>>|null */
    private ?array $resolvedOptions = null;

    /** @param array<int|string, string|array<int|string, string>>|class-string<BackedEnum>|Closure(): array<int|string, string|array<int|string, string>> $options */
    public function options(array|string|Closure $options): static
    {
        $this->optionSource = $options;
        $this->resolvedOptions = null;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function expanded(bool $expanded = true): static
    {
        $this->expanded = $expanded;
        return $this;
    }

    /** Label of the empty option of a single select (e.g. „Bitte wählen“). */
    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /** @return array<int|string, string|array<int|string, string>> */
    public function getOptions(): array
    {
        if (null !== $this->resolvedOptions) {
            return $this->resolvedOptions;
        }

        $source = $this->optionSource;
        if ($source instanceof Closure) {
            $source = $source();
        }
        if (is_string($source)) {
            $options = [];
            foreach ($source::cases() as $case) {
                $options[$case->value] = method_exists($case, 'label') ? (string) $case->label() : $case->name;
            }
            $source = $options;
        }

        return $this->resolvedOptions = $source;
    }

    /** @return array<int|string, string> options without groups */
    public function flatOptions(): array
    {
        $flat = [];
        foreach ($this->getOptions() as $value => $label) {
            if (is_array($label)) {
                foreach ($label as $groupValue => $groupLabel) {
                    $flat[$groupValue] = $groupLabel;
                }
                continue;
            }
            $flat[$value] = $label;
        }
        return $flat;
    }

    #[Override]
    protected function columnType(): string
    {
        return $this->multiple ? 'text' : 'varchar(191)';
    }

    #[Override]
    protected function columnDefault(): ?string
    {
        return $this->multiple || null === $this->default ? null : (string) $this->default;
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        if (!$this->multiple) {
            return null === $raw ? null : (string) $raw;
        }
        if (null === $raw || '' === $raw) {
            return [];
        }
        $values = json_decode((string) $raw, true);
        return is_array($values) ? array_values(array_map('strval', $values)) : [];
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        if (!$this->multiple) {
            return parent::toDb($value);
        }
        $value = is_array($value) ? array_values($value) : [];
        return [$this->name => [] === $value ? null : json_encode($value, JSON_UNESCAPED_UNICODE)];
    }

    #[Override]
    public function defaultValue(): mixed
    {
        if ($this->multiple) {
            return is_array($this->default) ? $this->default : (null === $this->default ? [] : [(string) $this->default]);
        }
        return null === $this->default ? null : (string) $this->default;
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        if ($this->multiple) {
            $values = is_array($input) ? $input : [];
            return array_values(array_filter(array_map('strval', $values), static fn (string $v): bool => '' !== $v));
        }
        return parent::fromInput($input);
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        $errors = parent::validate($value, $context);
        if ($this->isEmpty($value)) {
            return $errors;
        }
        $options = $this->flatOptions();
        foreach ($this->multiple ? (array) $value : [$value] as $item) {
            if (!array_key_exists((string) $item, $options) && !array_key_exists((int) $item, $options)) {
                $errors[] = I18n::rawMsg('yform_validation_choice', $this->translatedLabel());
                break;
            }
        }
        return $errors;
    }

    /** Whether the given option value is selected in the current value. */
    public function isSelected(int|string $option, mixed $value): bool
    {
        $selected = $this->multiple ? (array) $value : [$value];
        return in_array((string) $option, array_map('strval', array_filter($selected, static fn ($v) => null !== $v)), true);
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        $options = $this->flatOptions();
        $labels = [];
        foreach ($this->multiple ? (array) $value : [$value] as $item) {
            if (null !== $item && '' !== $item) {
                $labels[] = I18n::translate($options[$item] ?? (string) $item, false);
            }
        }
        return implode(', ', $labels);
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return \Redaxo\Core\View\escape($this->textValue($value));
    }

    /** Multiple choices match records containing any of the selected options. */
    #[Override]
    public function searchFilter(): ?Filter
    {
        $options = array_map(static fn (string $label): string => I18n::translate($label, false), $this->flatOptions());
        if (!$this->multiple) {
            return new ChoiceFilter($this, $options);
        }
        return new ChoiceFilter($this, $options, function (Query $query, array $values): void {
            $query->whereAny(function (Query $q) use ($values): void {
                foreach ($values as $value) {
                    $q->whereJsonContains($this->name, $value);
                }
            });
        });
    }
}
