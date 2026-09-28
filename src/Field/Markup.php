<?php

namespace Yakamara\YForm\Field;

use Closure;
use Override;
use Yakamara\YForm\Form\FieldContext;

/**
 * Static content between fields (hint, heading, privacy text). Stores nothing.
 *
 * `Markup::new('privacy_hint')->html('<p>…</p>')` or with a closure receiving the field context.
 */
class Markup extends Field
{
    /** @var string|Closure(FieldContext): string */
    private string|Closure $html = '';

    #[Override]
    protected function init(): void
    {
        $this->listed = false;
        $this->sortable = false;
    }

    /** @param string|Closure(FieldContext): string $html trusted HTML */
    public function html(string|Closure $html): static
    {
        $this->html = $html;
        return $this;
    }

    #[Override]
    public function storesValue(): bool
    {
        return false;
    }

    #[Override]
    public function columns(): array
    {
        return [];
    }

    #[Override]
    public function wrapped(): bool
    {
        return false;
    }

    #[Override]
    public function render(FieldContext $context): string
    {
        return $this->html instanceof Closure ? ($this->html)($context) : $this->html;
    }
}
