<?php

namespace Yakamara\YForm\Backend;

use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Component\ColumnType;
use Redaxo\Core\View\Component\Section;
use Redaxo\Core\View\Component\Table as TableComponent;
use Redaxo\Core\View\Component\TableColumn;
use Redaxo\Core\View\Html;
use Redaxo\Core\View\HtmlAttributes;
use Redaxo\Core\View\Message;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Form\Layout\LayoutStore;
use Yakamara\YForm\Schema\SchemaPlan;
use Yakamara\YForm\Schema\SchemaSync;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;

use function count;
use function implode;
use function Redaxo\Core\View\escape;

/**
 * Admin overview of all table definitions with their schema state.
 *
 * @internal
 */
final class TablesPage
{
    public static function run(): string
    {
        $message = self::handleSync();
        $csrf = CsrfToken::factory('yform_schema');

        if ([] === Tables::all()) {
            return $message . Message::info(I18n::msg('yform_tables_empty'));
        }

        $rows = [];
        foreach (Tables::all() as $key => $table) {
            $plan = SchemaSync::plan($table);
            $rows[] = [
                'icon' => Html::raw('<i class="rex-icon ' . escape($table->icon()) . '"></i>'),
                'table' => Html::raw('<a href="' . escape(Url::backendPage('yform/data', ['table' => $key])) . '"><strong>' . escape($table->label()) . '</strong></a><br><code>' . escape($table->name) . '</code>'),
                'definition' => Html::raw('<code>' . escape($table::class) . '</code><br><small class="rex-u-text-muted">' . escape(self::relativePath($table)) . '</small>'),
                'fields' => count($table->getFields()),
                'records' => $plan->create ? '–' : (string) $table->query()->count(),
                'schema' => Html::raw(self::planSummary($plan)),
                'sync' => Html::raw(self::syncForm($table, $plan, $csrf)),
                'designer' => Html::raw('<a href="' . escape(Url::backendPage('yform/designer', ['table' => $key])) . '"><i class="rex-icon fa-th-large"></i> ' . I18n::msg('yform_designer') . '</a>'
                    . (LayoutStore::exists($table::class) ? ' <span class="rex-badge" data-variant="info" title="' . I18n::msg('yform_layout_file_exists') . '">JSON</span>' : '')),
            ];
        }

        return $message . new Section(I18n::msg('yform_tables'), content: new TableComponent([
            TableColumn::forKey('', 'icon', ColumnType::Icon),
            TableColumn::forKey(I18n::msg('yform_table'), 'table'),
            TableColumn::forKey(I18n::msg('yform_definition'), 'definition'),
            TableColumn::forKey(I18n::msg('yform_fields'), 'fields'),
            TableColumn::forKey(I18n::msg('yform_records'), 'records'),
            TableColumn::forKey(I18n::msg('yform_schema'), 'schema'),
            TableColumn::forKey(I18n::msg('yform_actions'), 'sync', ColumnType::Action),
            TableColumn::forKey(null, 'designer', ColumnType::Action),
        ], $rows, attributes: new HtmlAttributes(['data-hover' => true])))->render();
    }

    private static function handleSync(): string
    {
        $key = Request::post('sync', 'string', '');
        if ('' === $key) {
            return '';
        }
        if (!CsrfToken::factory('yform_schema')->isValid()) {
            return Message::error(I18n::msg('csrf_token_invalid'));
        }
        $table = Tables::find($key);
        if (null === $table) {
            return Message::error(I18n::msg('yform_table_not_found'));
        }
        try {
            /** @var list<string> $drop */
            $drop = Request::post('drop', 'array[string]', []);
            SchemaSync::apply($table, $drop);
        } catch (Throwable $e) {
            return Message::error(escape($e->getMessage()));
        }
        return Message::success(I18n::msg('yform_schema_synced', escape($table->name)));
    }

    private static function planSummary(SchemaPlan $plan): string
    {
        if ($plan->create) {
            return '<span class="rex-badge" data-variant="warning">' . I18n::msg('yform_schema_create') . '</span>';
        }

        $parts = [];
        if ([] !== $plan->add) {
            $parts[] = '<span class="rex-badge" data-variant="warning">+ ' . escape(implode(', ', $plan->add)) . '</span>';
        }
        if ([] !== $plan->modify) {
            $parts[] = '<span class="rex-badge" data-variant="warning">~ ' . escape(implode(', ', $plan->modify)) . '</span>';
        }
        if ([] !== $plan->indexes) {
            $parts[] = '<span class="rex-badge" data-variant="warning">' . I18n::msg('yform_schema_indexes') . ': ' . escape(implode(', ', $plan->indexes)) . '</span>';
        }
        if ([] !== $plan->additional) {
            $parts[] = '<span class="rex-badge" data-variant="warning">' . I18n::msg('yform_schema_additional') . ': ' . escape(implode(', ', $plan->additional)) . '</span>';
        }
        if ([] !== $plan->obsolete) {
            $parts[] = '<span class="rex-badge" title="' . I18n::msg('yform_schema_obsolete_hint') . '">' . I18n::msg('yform_schema_obsolete') . ': ' . escape(implode(', ', $plan->obsolete)) . '</span>';
        }
        return [] === $parts ? '<span class="rex-badge" data-variant="success">' . I18n::msg('yform_schema_ok') . '</span>' : implode(' ', $parts);
    }

    private static function syncForm(Table $table, SchemaPlan $plan, CsrfToken $csrf): string
    {
        if (!$plan->hasChanges() && [] === $plan->obsolete) {
            return '';
        }
        $drop = '';
        foreach ($plan->obsolete as $column) {
            $drop .= '<label class="yform-drop"><input type="checkbox" name="drop[]" value="' . escape($column) . '"> ' . I18n::msg('yform_schema_drop', $column) . '</label>';
        }
        return '<form method="post" action="' . escape(Url::currentBackendPage()) . '">' . $csrf->getHiddenField()
            . '<input type="hidden" name="sync" value="' . escape($table->name) . '">' . $drop
            . '<button class="rex-button" data-variant="primary" data-size="xs" type="submit" data-confirm="' . I18n::msg('yform_schema_sync_confirm') . '">' . I18n::msg('yform_schema_sync') . '</button></form>';
    }

    private static function relativePath(Table $table): string
    {
        $file = (string) new ReflectionClass($table)->getFileName();
        return Path::relative($file, Path::base());
    }
}
