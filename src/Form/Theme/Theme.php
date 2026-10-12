<?php

namespace Yakamara\YForm\Form\Theme;

use Closure;

use function sprintf;

/**
 * CSS class vocabulary used by field and form views.
 *
 * The backend uses {@see backend()} (Bootstrap 3 plus the REDAXO panel footer). For the frontend pick one of the
 * presets or construct your own with named arguments; for markup changes beyond class names override the views
 * with `ViewResolver::override()`.
 *
 * `$column` maps a layout width (1–12) to classes: either a `sprintf` pattern (`col-md-%d`) or a closure for grids
 * that do not count in twelfths or need several breakpoints:
 *
 * ```php
 * new Theme(column: fn (int $width) => 'col-12 col-lg-' . $width);
 * ```
 *
 * With `$tabToggle = ''` tabs are switched by the YForm frontend script (`assets/yform-frontend.js`) instead of
 * a framework plugin; without JavaScript all tabs stay visible one below the other.
 */
final readonly class Theme
{
    public function __construct(
        public string $form = '',
        public string $group = 'form-group',
        public string $groupInvalid = 'has-error',
        public string $label = 'control-label',
        public string $control = 'form-control',
        public string $staticControl = 'form-control-static',
        public string $controlInvalid = '',
        public string $select = 'form-control',
        public string $checkWrapper = 'checkbox',
        public string $checkInput = '',
        public string $checkLabel = '',
        public string $radioWrapper = 'radio',
        public string $help = 'help-block',
        public string $error = 'help-block',
        public string $row = 'row',
        /** @var string|Closure(int): string */
        public string|Closure $column = 'col-sm-%d',
        public string $fieldset = '',
        public string $legend = '',
        public string $tabs = 'nav nav-tabs',
        public string $tabToggle = 'data-toggle',
        public string $tabItem = '',
        public string $tabLink = '',
        public string $tabActive = 'active',
        public bool $tabActiveOnItem = true,
        public string $tabPane = 'tab-pane',
        public string $tabPaneActive = 'active',
        public string $tabContent = 'tab-content',
        public string $buttons = 'form-group',
        public string $buttonToolbar = '',
        public string $submit = 'btn btn-primary',
        public string $secondary = 'btn btn-default',
        public string $alertError = 'alert alert-danger',
        public string $alertSuccess = 'alert alert-success',
        public string $inputGroup = 'input-group',
        public string $inputGroupAddon = 'input-group-addon',
    ) {}

    public static function bootstrap3(): self
    {
        return new self();
    }

    /** REDAXO backend: Bootstrap 3 with the backend's panel footer and save/apply buttons. */
    public static function backend(): self
    {
        return new self(
            buttons: 'rex-form-panel-footer',
            buttonToolbar: 'btn-toolbar',
            submit: 'btn btn-save',
            secondary: 'btn btn-apply',
        );
    }

    public static function bootstrap5(): self
    {
        return new self(
            group: 'mb-3',
            groupInvalid: '',
            label: 'form-label',
            controlInvalid: 'is-invalid',
            staticControl: 'form-control-plaintext',
            select: 'form-select',
            checkWrapper: 'form-check',
            checkInput: 'form-check-input',
            checkLabel: 'form-check-label',
            radioWrapper: 'form-check',
            help: 'form-text',
            error: 'invalid-feedback d-block',
            column: 'col-md-%d',
            legend: 'fs-5',
            tabToggle: 'data-bs-toggle',
            tabItem: 'nav-item',
            tabLink: 'nav-link',
            tabActiveOnItem: false,
            tabPane: 'tab-pane fade',
            tabPaneActive: 'active show',
            buttons: 'mt-3',
            secondary: 'btn btn-secondary',
            inputGroupAddon: 'input-group-text',
        );
    }

    /**
     * Tailwind CSS utility classes. The grid classes are spelled out literally so that Tailwind's content scan finds
     * them; add `vendor/yakamara/yform/src/Form/Theme` to the scanned sources (`@source` in Tailwind 4).
     */
    public static function tailwind(): self
    {
        return new self(
            form: 'space-y-6',
            group: 'space-y-1',
            groupInvalid: '',
            label: 'block text-sm font-medium text-gray-700',
            control: 'block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
            staticControl: 'py-2 text-gray-900',
            controlInvalid: 'border-red-500',
            select: 'block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
            checkWrapper: 'flex items-center gap-2',
            checkInput: 'size-4 rounded border-gray-300 text-blue-600',
            checkLabel: 'text-sm text-gray-700',
            radioWrapper: 'flex items-center gap-2',
            help: 'text-sm text-gray-500',
            error: 'text-sm text-red-600',
            row: 'grid grid-cols-12 gap-4',
            column: static fn (int $width): string => match ($width) {
                1 => 'col-span-12 md:col-span-1',
                2 => 'col-span-12 md:col-span-2',
                3 => 'col-span-12 md:col-span-3',
                4 => 'col-span-12 md:col-span-4',
                5 => 'col-span-12 md:col-span-5',
                6 => 'col-span-12 md:col-span-6',
                7 => 'col-span-12 md:col-span-7',
                8 => 'col-span-12 md:col-span-8',
                9 => 'col-span-12 md:col-span-9',
                10 => 'col-span-12 md:col-span-10',
                11 => 'col-span-12 md:col-span-11',
                default => 'col-span-12',
            },
            fieldset: 'space-y-4',
            legend: 'text-lg font-semibold text-gray-900',
            tabs: 'flex gap-6 border-b border-gray-200',
            tabToggle: '',
            tabItem: '',
            // Utilities do not override each other by order, so the active state uses the `aria-selected` variant.
            tabLink: '-mb-px block border-b-2 border-transparent py-2 text-sm font-medium text-gray-500 hover:text-gray-700 aria-selected:border-blue-600 aria-selected:text-blue-600',
            tabActive: '',
            tabActiveOnItem: false,
            tabPane: 'space-y-4 pt-4',
            tabPaneActive: '',
            tabContent: '',
            buttons: 'flex gap-3',
            submit: 'rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700',
            secondary: 'rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50',
            alertError: 'rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800',
            alertSuccess: 'rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800',
            inputGroup: 'flex',
            inputGroupAddon: 'inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500',
        );
    }

    /**
     * Minimal, framework-free markup with `yform-*` classes. `assets/yform-frontend.css` provides the grid and a
     * neutral base style, `assets/yform-frontend.js` the tabs.
     */
    public static function plain(): self
    {
        return new self(
            form: 'yform',
            group: 'yform-group',
            groupInvalid: 'yform-group--invalid',
            label: 'yform-label',
            control: 'yform-control',
            select: 'yform-control',
            checkWrapper: 'yform-check',
            radioWrapper: 'yform-check',
            help: 'yform-help',
            error: 'yform-error',
            row: 'yform-row',
            column: 'yform-col yform-col--%d',
            staticControl: 'yform-static',
            tabs: 'yform-tabs',
            tabToggle: '',
            tabItem: 'yform-tabs-item',
            tabLink: 'yform-tabs-link',
            tabActive: 'is-active',
            tabActiveOnItem: false,
            tabPane: 'yform-tab',
            tabPaneActive: 'is-active',
            tabContent: 'yform-tab-content',
            buttons: 'yform-buttons',
            submit: 'yform-submit',
            secondary: 'yform-button',
            alertError: 'yform-alert yform-alert--error',
            alertSuccess: 'yform-alert yform-alert--success',
            inputGroup: 'yform-input-group',
            inputGroupAddon: 'yform-input-group-addon',
        );
    }

    /** Whether tabs are rendered as tab navigation; without it they are rendered one below the other. */
    public function supportsTabs(): bool
    {
        return '' !== $this->tabs;
    }

    public function columnClass(int $width): string
    {
        return $this->column instanceof Closure ? ($this->column)($width) : sprintf($this->column, $width);
    }

    /** Whether tabs are switched by the YForm frontend script instead of a framework plugin. */
    public function usesOwnTabs(): bool
    {
        return '' === $this->tabToggle;
    }
}
