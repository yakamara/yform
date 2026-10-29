<?php

use Redaxo\Core\Core;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\Util\Formatter;
use Yakamara\YForm\Backend\UploadDownload;
use Yakamara\YForm\Field\Upload;
use Yakamara\YForm\Field\Upload\PendingUpload;
use Yakamara\YForm\Form\FieldContext;

use function Redaxo\Core\View\escape;

/** Kept files (with a checkbox to remove them) and the file input. Waiting uploads are carried along by their token. */
return static function (Upload $field, FieldContext $context): void {
    $entries = [];
    foreach ($field->entries($context->value) as $entry) {
        if (!$entry instanceof PendingUpload || !$entry->isRejected()) {
            $entries[] = $entry;
        } elseif (null !== $entry->previous && null !== $previous = $field->entryFromKey($entry->previous)) {
            $entries[] = $previous;
        }
    }

    $record = $context->record;
    $link = static function (string|PendingUpload $entry) use ($field, $record): ?string {
        if (!is_string($entry) || null === $record?->id || !Core::isBackend()) {
            return null;
        }
        return $field->mediaPool ? Url::media($entry) : UploadDownload::url($field, $record, $entry);
    };

    if ([] !== $entries) {
        echo '<ul class="yform-upload-files">';
        foreach ($entries as $index => $entry) {
            $key = Upload::key($entry);
            $name = is_string($entry) ? $entry : $entry->name;
            $url = $link($entry);
            $removeId = $context->id . '-remove-' . $index;
            echo '<li>';
            echo '<input type="hidden" name="', escape($context->inputName('[files][]')), '" value="', escape($key), '">';
            echo null === $url ? '<span>' . escape($name) . '</span>' : '<a href="' . escape($url) . '"' . ($field->mediaPool ? ' target="_blank" rel="noopener"' : '') . '>' . escape($name) . '</a>';
            if ($entry instanceof PendingUpload) {
                echo ' <small class="yform-upload-pending">(', escape(Formatter::bytes($entry->size)), ', ', I18n::msg('yform_upload_pending'), ')</small>';
            }
            if (!$field->readonly) {
                echo ' <label class="yform-upload-remove" for="', escape($removeId), '">';
                echo '<input type="checkbox" id="', escape($removeId), '" name="', escape($context->inputName('[remove][]')), '" value="', escape($key), '"> ';
                echo I18n::msg('yform_upload_remove'), '</label>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    if ($field->readonly) {
        return;
    }

    $accept = [];
    foreach ($field->types as $type) {
        $accept[] = '.' . $type;
    }
    $attributes = $field->controlAttributes($context, [
        'type' => 'file',
        'name' => $context->inputName($field->multiple ? '[upload][]' : '[upload]'),
        'multiple' => $field->multiple,
        'accept' => [] === $accept ? null : implode(',', $accept),
        'required' => $field->required && [] === $entries,
    ]);
    echo '<input ', $attributes, '>';

    $hints = [];
    if ([] !== $field->types) {
        $hints[] = implode(', ', $field->types);
    }
    if (null !== $field->maxSize) {
        $hints[] = I18n::rawMsg('yform_upload_max_size', Formatter::bytes($field->maxSize));
    }
    if ([] !== $hints) {
        echo '<small class="yform-upload-hint">', escape(implode(' · ', $hints)), '</small>';
    }
};
