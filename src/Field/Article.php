<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Content\Article as CoreArticle;
use Redaxo\Core\Core;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Search\Filter;
use Yakamara\YForm\Validation\ValidationContext;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function implode;
use function is_array;
use function json_decode;
use function json_encode;
use function Redaxo\Core\View\escape;

/**
 * Link to a structure article (backend link map widget). Single article as `int`, `->multiple()` as JSON array.
 *
 * Deleting an article that is referenced here is blocked (see `ART_PRE_DELETED` in the addon).
 */
class Article extends Field
{
    public protected(set) bool $multiple = false;
    public protected(set) ?int $category = null;

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /** Category opened by default in the link map. */
    public function category(?int $category): static
    {
        $this->category = $category;
        return $this;
    }

    #[Override]
    protected function columnType(): string
    {
        return $this->multiple ? 'text' : 'int(10) unsigned';
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        if (!$this->multiple) {
            return null === $raw || 0 === (int) $raw ? null : (int) $raw;
        }
        $values = null === $raw || '' === $raw ? [] : json_decode((string) $raw, true);
        return is_array($values) ? array_values(array_map('intval', $values)) : [];
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        if (!$this->multiple) {
            return [$this->name => $this->isEmpty($value) ? null : (int) $value];
        }
        $value = is_array($value) ? array_values(array_map('intval', $value)) : [];
        return [$this->name => [] === $value ? null : json_encode($value)];
    }

    #[Override]
    public function defaultValue(): mixed
    {
        return $this->multiple ? [] : $this->default;
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        if (!$this->multiple) {
            $value = parent::fromInput($input);
            return null === $value || 0 === (int) $value ? null : (int) $value;
        }
        $input = is_array($input) ? implode(',', $input) : (string) $input;
        return array_values(array_filter(array_map('intval', explode(',', $input))));
    }

    /** @return list<int> */
    public function articleIds(mixed $value): array
    {
        if ($this->multiple) {
            return is_array($value) ? $value : [];
        }
        return null === $value ? [] : [(int) $value];
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        $errors = parent::validate($value, $context);
        foreach ($this->articleIds($value) as $id) {
            if (null === CoreArticle::get($id)) {
                $errors[] = I18n::rawMsg('yform_validation_article_missing', $this->translatedLabel(), $id);
            }
        }
        return $errors;
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        $names = [];
        foreach ($this->articleIds($value) as $id) {
            $article = CoreArticle::get($id);
            $names[] = null === $article ? '[' . $id . ']' : $article->name . ' [' . $id . ']';
        }
        return implode(', ', $names);
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return escape($this->textValue($value));
    }

    #[Override]
    public function isEditable(): bool
    {
        return parent::isEditable() && Core::isBackend();
    }

    #[Override]
    public function searchFilter(): ?Filter
    {
        return null;
    }
}
