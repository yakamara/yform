<?php

namespace Yakamara\YForm\Usage;

use Redaxo\Core\ExtensionPoint\AsExtension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Field\Media;
use Yakamara\YForm\Table\Tables;

use function Redaxo\Core\View\escape;

/**
 * Prevents deleting media pool files that are referenced by a {@see Media} field.
 *
 * @internal
 */
final class MediaUsage
{
    /**
     * @param ExtensionPoint<list<string>> $ep
     *
     * @return list<string>
     */
    #[AsExtension('MEDIA_IS_IN_USE')]
    public static function isMediaInUse(ExtensionPoint $ep): array
    {
        $warnings = $ep->subject;
        $fileName = (string) $ep->getParam('filename');

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
                $items = '';
                foreach ($query->limit(20)->find() as $record) {
                    $url = Url::backendPage('yform/data', ['table' => $table->name, 'func' => 'edit', 'id' => $record->id]);
                    $items .= '<li><a href="' . escape($url) . '">' . escape($table->label() . ': ' . $record->label()) . '</a></li>';
                }
                if ('' !== $items) {
                    $warnings[] = I18n::msg('yform_media_in_use', escape($field->translatedLabel())) . '<ul>' . $items . '</ul>';
                }
            }
        }

        return $warnings;
    }
}
