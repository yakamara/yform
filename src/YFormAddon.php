<?php

namespace Yakamara\YForm;

use Override;
use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Backend\MainPage;
use Redaxo\Core\Backend\Page;
use Redaxo\Core\Core;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Security\ComplexPermission;
use Redaxo\Core\View\Asset;
use Yakamara\YForm\Permission\Access;
use Yakamara\YForm\Permission\TableEditPermission;
use Yakamara\YForm\Permission\TableViewPermission;
use Yakamara\YForm\Schema\SchemaSync;
use Yakamara\YForm\Table\Tables;

final class YFormAddon extends Addon
{
    #[Override]
    public function boot(): void
    {
        ComplexPermission::register(TableEditPermission::KEY, TableEditPermission::class);
        ComplexPermission::register(TableViewPermission::KEY, TableViewPermission::class);

        if (Core::isBackend() && null !== Core::getUser()) {
            Asset::addCssFile($this->getAssetsUrl('yform.css'));
            Asset::addJsFile($this->getAssetsUrl('yform.js'));
            // switches the tabs of the backend theme
            Asset::addJsFile($this->getAssetsUrl('yform-frontend.js'));
            // Assets must be registered before the page renders, so this can not live in the designer page itself.
            if ('yform/designer' === Request::get('page', 'string', '')) {
                Asset::addJsFile($this->getAssetsUrl('designer.js'));
            }
        }
    }

    /**
     * Runs on every `console migrate`: creates and updates the tables of all definitions. Obsolete columns are
     * kept; drop them on the schema page or with `yform:schema --drop`.
     */
    #[Override]
    public function install(): void
    {
        SchemaSync::applyAll();
    }

    /** @return iterable<Page> */
    #[Override]
    public function getPages(): iterable
    {
        $user = Core::getUser();
        $isAdmin = $user?->admin ?? false;

        $main = new MainPage('addons', 'yform', $this->i18n('yform'));
        $main->setIcon('rex-icon fa-brands fa-wpforms');

        $main->addSubpage(new Page('tables', $this->i18n('tables'))->setRequiredPermissions('admin'));
        $main->addSubpage(new Page('forms', $this->i18n('forms'))->setRequiredPermissions('admin'));
        $main->addSubpage(new Page('designer', $this->i18n('designer'))->setHidden()->setRequiredPermissions('admin'));
        // Data pages are reached through the per-table navigation entries; access is checked per table.
        $main->addSubpage(new Page('data', '')->setHidden());

        if (!$isAdmin) {
            $main->setHidden();
        }

        $activeKey = 'yform/data' === Request::request('page', 'string') ? Request::request('table', 'string') : null;
        if (null !== $activeKey && null !== Tables::find($activeKey)) {
            $main->setIsActive(false);
        }

        yield $main;

        if (null === $user) {
            return;
        }

        $prio = 1;
        foreach (Tables::all() as $key => $table) {
            if (!$table->definition->navigation || !Access::canView($table, $user)) {
                continue;
            }
            $page = new MainPage('yform_tables', 'yform_table_' . $key, $table->label());
            $page->setHref(Url::backendPage('yform/data', ['table' => $key]));
            $page->setIcon('rex-icon ' . $table->icon());
            $page->setPrio($prio++);
            if ($key === $activeKey) {
                $page->setIsActive();
            }
            yield $page;
        }
    }
}
