<?php

namespace Yakamara\YForm;

use Override;
use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Backend\MainPage;
use Redaxo\Core\Backend\Page;
use Redaxo\Core\Core;
use Redaxo\Core\Cronjob\CronjobExecutor;
use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Index;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use Redaxo\Core\Exception\RuntimeException;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Security\ComplexPermission;
use Redaxo\Core\View\Asset;
use Throwable;
use Yakamara\YForm\Cronjob\HistoryDelete;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Table\Perm\Edit as PermEdit;
use Yakamara\YForm\Manager\Table\Perm\View as PermView;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Value\BackendLink;
use Yakamara\YForm\Value\BackendMedia;

use function count;
use function in_array;

class YFormAddon extends Addon
{
    #[Override]
    public function boot(): void
    {
        ComplexPermission::register('yform_manager_table_edit', PermEdit::class);
        ComplexPermission::register('yform_manager_table_view', PermView::class);

        Extension::register('MEDIA_IS_IN_USE', BackendMedia::isMediaInUse(...));
        Extension::register('PACKAGES_INCLUDED', BackendLink::isArticleInUse(...));

        // The email templates are editable in the backend, so an "open in editor" link has to point at
        // that page instead of a file on disk.
        Extension::register('EDITOR_URL', static function (ExtensionPoint $ep) {
            if (preg_match('@^rex:///yform/email/template/(.*)/(.*)@', (string) $ep->getParam('file'), $match)) {
                return Url::backendPage('yform/email/index', [
                    'func' => 'edit',
                    'template_key' => $match[1],
                ]);
            }

            return null;
        });

        Extension::register('YFORM_SAVED', static function (ExtensionPoint $ep): void {
            if ($ep->subject instanceof Throwable) {
                return;
            }

            $table = Table::get($ep->getParam('table'));
            if (!$table) {
                return;
            }

            $dataset = $ep->getParam('form')->getParam('manager_dataset');
            if (!$dataset) {
                $dataset = Dataset::getRaw($ep->getParam('id'), $table->getTableName());
            }
            $dataset->invalidateData();

            if ($table->hasHistory() && $dataset->isHistoryEnabled()) {
                $action = 'insert' === $ep->getParam('action') ? Dataset::ACTION_CREATE : Dataset::ACTION_UPDATE;
                $dataset->makeSnapshot($action);
            }
        });

        // Cronjobs are part of the core in REDAXO 6, so this no longer depends on a separate addon.
        CronjobExecutor::registerType(HistoryDelete::class);

        if (Core::isBackend() && null !== Core::getUser()) {
            Asset::addCssFile($this->getAssetsUrl('yform-styles.css'));

            Asset::addJsFile($this->getAssetsUrl('manager.js'));
            Asset::addJsFile($this->getAssetsUrl('relations.js'));
            Asset::addJsFile($this->getAssetsUrl('widget.js'));

            Asset::addJsFile($this->getAssetsUrl('daterangepicker/moment.min.js'));
            Asset::addJsFile($this->getAssetsUrl('daterangepicker/daterangepicker.js'));
            Asset::addCssFile($this->getAssetsUrl('daterangepicker/daterangepicker.css'));
            Asset::addJsFile($this->getAssetsUrl('inputmask/dist/jquery.inputmask.min.js'));
            Asset::addJsFile($this->getAssetsUrl('tools.js'));
        }
    }

    /**
     * @return iterable<Page>
     */
    #[Override]
    public function getPages(): iterable
    {
        $user = Core::getUser();
        $isAdmin = $user?->admin ?? false;

        $main = new MainPage('addons', 'yform', $this->i18n('yform'));
        $main->setIcon('rex-icon rex-icon-module');

        // In REDAXO 5 the manager subpage was pushed to the front from a PAGE_CHECKED listener because the
        // page tree came from package.yml. Here the order is simply the order it is built in.
        if ($isAdmin) {
            $manager = new Page('manager', $this->i18n('manager'))
                ->setRequiredPermissions('admin');
            $manager->addSubpage(new Page('table_edit', $this->i18n('manager_table_edit'))->setRequiredPermissions('admin'));
            $manager->addSubpage(new Page('table_migrate', $this->i18n('manager_table_migrate'))->setRequiredPermissions('admin'));
            $manager->addSubpage(new Page('tableset_export', $this->i18n('manager_tableset_export'))->setRequiredPermissions('admin'));
            $manager->addSubpage(new Page('tableset_import', $this->i18n('manager_tableset_import'))->setRequiredPermissions('admin'));
            $manager->addSubpage(new Page('table_field', '')->setHidden()->setRequiredPermissions('admin'));
            $manager->addSubpage(new Page('data_edit', '')->setHidden());
            // pages/manager.data_import.php and .data_history.php are deliberately NOT registered:
            // both are `include`d from Manager (with $this being the Manager, not the addon) while
            // data_edit handles func=import / func=history. REDAXO 5 still declared data_import as a
            // hidden subpage, but requesting it directly errored there too.
            $main->addSubpage($manager);

            $main->addSubpage(new Page('setup', $this->i18n('setup'))->setRequiredPermissions('admin'));
        }

        $main->addSubpage(
            new Page('email', $this->i18n('email_templates'))
                ->setRequiredPermissions('admin[]')
                ->setPjax(),
        );

        $main->addSubpage(
            new Page('docs', $this->i18n('docs'))
                ->setRequiredPermissions('admin[]')
                ->addItemClass('pull-right')
                ->setIcon('rex-icon fa-info-circle'),
        );

        // REDAXO 5 hid the yform entry for everyone but admins (boot.php set
        // $page['hidden'] = true for non-admins). A non-admin editor is meant to see only
        // the per-table entries below, not the yform menu itself — even though `email` and
        // `docs` carry an `admin[]` permission of their own.
        if (!$isAdmin || 1 > count($main->getSubpages())) {
            $main->setHidden();
        }

        // One navigation entry per managed table, mirroring REDAXO 5's dynamic pages. Doing this in
        // getPages() rather than in boot() means it runs only for backend requests that build the menu.
        $tables = [];
        if (null !== $user) {
            try {
                $tables = Table::getAll();
            } catch (Throwable) {
                // The tables are unknown before install/migrate has run.
                $tables = [];
            }
        }

        // Which table page — if any — this request activates. Determined before yielding
        // $main, because the main page's own state depends on it.
        $activeTable = null;
        if ('yform/manager/data_edit' === Request::request('page', 'string')) {
            $requested = Request::request('table_name', 'string');
            foreach ($tables as $table) {
                if ($table->getTableName() === $requested && $table->isActive() && $table->isGranted('VIEW', $user)) {
                    $activeTable = $table;
                    break;
                }
            }
        }

        if (null !== $activeTable) {
            // The table gets its own navigation entry, so the yform entry must not light
            // up as well — REDAXO 5 did this with $main_page['isActive'] = false.
            $main->setIsActive(false);

            // Popup mode (a relation field opening the data list in a modal) belongs on the
            // *main* page too, exactly as in REDAXO 5.
            //
            // Putting it on the per-table page instead looks equivalent but crashes: setPopup()
            // calls addItemClass(), which stores itemAttr['class'] as a *string*, and the
            // navigation fragment then runs `$item['itemAttr']['class'][] = 'active'` on it —
            // "[] operator not supported for strings". Only the combination of setPopup() and
            // an active page triggers it, which is why plain data_edit calls were fine.
            if ($this->isPopupRequest()) {
                $main->setPopup(true);
            }
        }

        yield $main;

        $prio = 1;
        foreach ($tables as $table) {
            if (!$table->isActive() || !$table->isGranted('VIEW', $user)) {
                continue;
            }

            $page = new MainPage('yform_tables', 'yform_table_' . $table->getTableName(), $table->getNameLocalized());
            $page->setHref(Url::backendPage('yform/manager/data_edit', ['table_name' => $table->getTableName()]));
            $page->setIcon('rex-icon ' . ($table->getCustomIcon() ?: 'rex-icon-module'));
            $page->setPrio($prio++);

            if ($table->isHidden()) {
                $page->setHidden();
            }

            if ($table === $activeTable) {
                // Popup mode is handled on the main page above — see the note there.
                $page->setIsActive();
            }

            yield $page;
        }
    }

    /**
     * Whether this request is a data list opened as a popup by a relation field —
     * either through `rex_yform_manager_popup=1` or through an opener id.
     */
    private function isPopupRequest(): bool
    {
        if ('yform/manager/data_edit' !== Request::request('page', 'string')) {
            return false;
        }

        if (1 === Request::request('rex_yform_manager_popup', 'int')) {
            return true;
        }

        $opener = Request::request('rex_yform_manager_opener', 'array');

        return isset($opener['id']) && '' !== (string) $opener['id'];
    }

    #[Override]
    public function install(): void
    {
        // yform wraps every dataset save in a transaction, so a storage engine without transaction
        // support would corrupt data rather than fail loudly.
        try {
            Sql::factory()->transactional(static function (): void {
                Sql::factory()->setQuery('SELECT * FROM ' . Core::getTable('user') . ' LIMIT 1');
            });
        } catch (Throwable $e) {
            // Exception\Exception is only a marker interface in REDAXO 6 — RuntimeException is the
            // concrete general-purpose class behind it.
            throw new RuntimeException('db does not support transactions: ' . $e->getMessage(), 0, $e);
        }

        $this->installEmailTables();
        $this->installManagerTables();

        Table::deleteCache();
    }

    private function installEmailTables(): void
    {
        DbTable::get(Core::getTable('yform_email_template'))
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('name', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('mail_from', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('mail_from_name', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('mail_reply_to', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('mail_reply_to_name', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('subject', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('body', 'text', true))
            ->ensureColumn(new Column('body_html', 'text', true))
            ->ensureColumn(new Column('attachments', 'text', true))
            ->ensureColumn(new Column('updatedate', 'datetime', true))
            ->ensureIndex(new Index('name', ['name'], Index::UNIQUE))
            ->ensure();
    }

    private function installManagerTables(): void
    {
        $table = DbTable::get(Core::getTable('yform_table'));
        $hasMassDeletion = $table->hasColumn('mass_deletion');
        $hasMassEdit = $table->hasColumn('mass_edit');

        $table
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('status', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('table_name', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('name', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('description', 'text', true))
            ->ensureColumn(new Column('table_icon', 'varchar(191)', true))
            ->ensureColumn(new Column('list_amount', 'int(11)', false, '50'))
            ->ensureColumn(new Column('list_sortfield', 'varchar(191)', false, 'id'))
            ->ensureColumn(new Column('list_sortorder', "enum('ASC','DESC')", false, 'ASC'))
            ->ensureColumn(new Column('prio', 'int(11)', false, '0'))
            ->ensureColumn(new Column('search', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('hidden', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('export', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('import', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('mass_deletion', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('mass_edit', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('schema_overwrite', 'tinyint(1)', false, '1'))
            ->ensureColumn(new Column('history', 'tinyint(1)', false, '0'))
            ->ensureIndex(new Index('table_name', ['table_name'], Index::UNIQUE))
            ->ensure();

        DbTable::get(Core::getTable('yform_field'))
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('table_name', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('prio', 'int(11)', false, '0'))
            ->ensureColumn(new Column('type_id', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('type_name', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('db_type', 'varchar(191)', true))
            ->ensureColumn(new Column('list_hidden', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('search', 'tinyint(1)', false, '0'))
            ->ensureColumn(new Column('name', 'text', true))
            ->ensureColumn(new Column('label', 'text', true))
            ->ensureColumn(new Column('not_required', 'text', true))
            ->ensureColumn(new Column('multiple', 'text', true))
            ->ensureColumn(new Column('expanded', 'text', true))
            ->ensureColumn(new Column('choices', 'text', true))
            ->ensureColumn(new Column('choice_attributes', 'text', true))
            ->ensure();

        $this->relaxFieldSettingColumns();

        DbTable::get(Core::getTable('yform_history'))
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('table_name', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('dataset_id', 'int(11)', false, '0'))
            ->ensureColumn(new Column('action', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('user', 'varchar(191)', true))
            ->ensureColumn(new Column('timestamp', 'datetime', true))
            ->ensureIndex(new Index('dataset', ['table_name', 'dataset_id']))
            ->ensure();

        DbTable::get(Core::getTable('yform_history_field'))
            ->ensureColumn(new Column('history_id', 'int(11)', false, '0'))
            ->ensureColumn(new Column('field', 'varchar(191)', false, ''))
            ->ensureColumn(new Column('value', 'longtext', true))
            ->setPrimaryKey(['history_id', 'field'])
            ->ensure();

        // Both flags were introduced later and default to on for tables that predate them.
        if (!$hasMassDeletion) {
            Sql::factory()->setTable(Core::getTable('yform_table'))->setValue('mass_deletion', 1)->update();
        }
        if (!$hasMassEdit) {
            Sql::factory()->setTable(Core::getTable('yform_table'))->setValue('mass_edit', 1)->update();
        }

        // The view permission was split off the combined one; roles created before that carry the old key.
        foreach (Sql::factory()->getArray('SELECT id, perms FROM ' . Core::getTable('user_role')) as $role) {
            if (!str_contains((string) $role['perms'], '"yform_manager_table_edit":')) {
                Sql::factory()->setQuery(
                    'UPDATE ' . Core::getTable('user_role') . ' SET perms = ? WHERE id = ?',
                    [str_replace('"yform_manager_table":', '"yform_manager_table_edit":', (string) $role['perms']), $role['id']],
                );
            }
        }
    }

    /**
     * Makes the per-field-type setting columns of `rex_yform_field` nullable.
     *
     * yform adds one column per field-type setting at runtime (`format`, `no_db`, `precision`, …), and
     * up to yform 5 it created them `TEXT NOT NULL` with no default. That was harmless under REDAXO 5,
     * which connected with `SQL_MODE=""`: an INSERT omitting them stored ''. REDAXO 6 connects with
     * STRICT_TRANS_TABLES, so saving a field of type A fails on every NOT NULL column that belongs to
     * some other type B. New columns are created nullable now; this relaxes the ones an existing
     * (REDAXO 5) installation already has.
     *
     * Only the columns yform generates are touched — the fixed schema above is left alone.
     */
    private function relaxFieldSettingColumns(): void
    {
        $fixed = [
            'id', 'table_name', 'prio', 'type_id', 'type_name', 'db_type', 'list_hidden', 'search',
            'name', 'label', 'not_required', 'multiple', 'expanded', 'choices', 'choice_attributes',
        ];

        $table = Core::getTable('yform_field');

        foreach (Sql::showColumns($table) as $column) {
            if (in_array($column['name'], $fixed, true) || 'NO' !== ($column['null'] ?? 'YES')) {
                continue;
            }

            Sql::factory()->setQuery(
                'ALTER TABLE `' . $table . '` MODIFY `' . $column['name'] . '` ' . $column['type'] . ' NULL',
            );
        }
    }

    #[Override]
    public function uninstall(): void
    {
        // Only yform's own bookkeeping is removed. The tables yform *manages* belong to the project and
        // are deliberately left in place — dropping them would destroy content, not addon state.
        foreach ([
            'yform_email_template',
            // The REST API is not part of the REDAXO 6 addon; its two tables are dropped here so that an
            // installation migrated from REDAXO 5 does not keep them behind.
            'yform_rest_token',
            'yform_rest_token_access',
            'yform_table',
            'yform_field',
            'yform_history',
            'yform_history_field',
        ] as $name) {
            $table = DbTable::get(Core::getTable($name));
            if ($table->exists()) {
                $table->drop();
            }
        }
    }
}
