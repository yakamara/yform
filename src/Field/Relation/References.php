<?php

namespace Yakamara\YForm\Field\Relation;

use Redaxo\Core\Database\Sql;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;

/**
 * Keeps references consistent when a record is deleted, according to {@see BelongsTo::$onDelete}.
 *
 * @internal
 */
final class References
{
    /** @throws DeleteRestricted */
    public static function beforeDelete(Table $table, Record $record): void
    {
        foreach (Tables::all() as $source) {
            foreach ($source->getFields() as $field) {
                if ($field instanceof ManyToMany) {
                    if ($field->targetTable() === $table) {
                        $sql = Sql::factory();
                        $sql->setQuery('DELETE FROM ' . $sql->escapeIdentifier($field->pivotName($source)) . ' WHERE target_id = ?', [$record->id]);
                    }
                    continue;
                }
                if (!$field instanceof BelongsTo || $field->targetTable() !== $table) {
                    continue;
                }
                $query = $source->query()->where($field->name, $record->id);
                match ($field->onDelete) {
                    OnDelete::Restrict => $query->exists() ? throw new DeleteRestricted(I18n::rawMsg('yform_delete_restricted', $record->label(), $source->label(), $query->count())) : null,
                    OnDelete::SetNull => Sql::factory()->setTable($source->name)->setWhere([$field->name => $record->id])->setValue($field->name, null)->update(),
                    OnDelete::Cascade => $query->delete(),
                };
            }
        }
    }
}
