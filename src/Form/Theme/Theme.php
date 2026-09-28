<?php

namespace Yakamara\YForm\Form\Theme;

use function sprintf;

/**
 * CSS class vocabulary used by field and form views.
 *
 * The backend always uses {@see bootstrap3()} (the REDAXO backend is Bootstrap 3). For the frontend pick one of the
 * presets or construct your own with named arguments; for markup changes beyond class names override the views
 * with `ViewResolver::override()`.
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
        public string $column = 'col-sm-%d',
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

    /** Minimal, framework-free markup with `yform-*` classes for own stylesheets. */
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
            tabs: '',
            tabPane: 'yform-tab',
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
        return sprintf($this->column, $width);
    }
}
