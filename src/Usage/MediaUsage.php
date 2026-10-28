<?php

namespace Yakamara\YForm\Usage;

use Redaxo\Core\ExtensionPoint\AsExtension;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\MediaPool\ExtensionPoint\MediaUsageCheck;
use Yakamara\YForm\Field\Media;
use Yakamara\YForm\Table\Tables;

/**
 * Prevents deleting media pool files that are referenced by a {@see Media} field.
 *
 * @internal
 */
final class MediaUsage
{
    #[AsExtension]
    public static function isMediaInUse(MediaUsageCheck $ep): void
    {
        $fileName = $ep->media->fileName;

        foreach (Tables::all() as $table) {
            foreach ($table->getFields() as $field) {
                if (!$field instanceof Media) {
                    continue;
                }
                $query = $table->query();
                if ($field->multiple) {
                    $query->whereJsonContains($field->name, $fileName);
                } else {
                    $query->where($field->name, $fileName);
                }
                foreach ($query->limit(20)->find() as $record) {
                    $url = Url::backendPage('yform/data', ['table' => $table->name, 'func' => 'edit', 'id' => $record->id]);
                    $ep->usages->add($table->label() . ' · ' . $field->translatedLabel(), $record->label(), $url);
                }
            }
        }
    }
}
