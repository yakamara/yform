<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Backend\Page;
use Yakamara\YForm\YFormAddon;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Security\User;
use Redaxo\Core\Core;
use ReflectionClass;
use ReflectionProperty;
use Throwable;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Authorization;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\AbstractTestSuite;
use Yakamara\YForm\YForm;

use function count;
use function in_array;

/**
 * Builds the four-table relation fixture used by RelationsSuite and rebuilds its
 * rows before every test, so each test starts from the same graph:
 *
 *   author  <──n:1──  post  ──n:m──>  tag        (through post_tag)
 *                      │
 *                      └──1:n──>  comment        (comment.post_id)
 *
 * @package redaxo\yform
 * @internal
 */
abstract class AbstractRelationFixture extends AbstractTestSuite
{
    /** short name => full table name */
    private array $tables = [];

    /** @var list<int> */
    protected array $authorIds = [];
    /** @var list<int> */
    protected array $tagIds = [];
    /** @var list<int> */
    protected array $postIds = [];

    protected function t(string $short): string
    {
        return $this->tables[$short];
    }

    public function setUpBeforeClass(): void
    {
        foreach (['author', 'tag', 'post_tag', 'comment', 'post'] as $short) {
            $this->tables[$short] = $this->fixtures->reserveTableName('rel_' . $short);
            $this->trackFixture($this->tables[$short]);
            $this->dropTable($this->tables[$short]);
        }

        // Order matters only for readability — the relations are resolved by name.
        $this->buildTable('author', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'name', 'label' => 'Name', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'email', 'name' => 'email', 'label' => 'E-Mail', 'prio' => 2],
        ]);

        $this->buildTable('tag', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'Titel', 'prio' => 1],
        ]);

        // All table names are reserved up front, so post_tag can already point at
        // `post` even though that table is created further down.
        $this->buildTable('post_tag', [
            ['type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'post_id', 'label' => 'Post',
                'table' => $this->t('post'), 'field' => 'title', 'type' => 0, 'empty_option' => 1, 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'tag_id', 'label' => 'Tag',
                'table' => $this->t('tag'), 'field' => 'title', 'type' => 0, 'empty_option' => 1, 'prio' => 2],
        ]);

        $this->buildTable('comment', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'author_name', 'label' => 'Von', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'textarea', 'name' => 'body', 'label' => 'Kommentar', 'prio' => 2],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'post_id', 'label' => 'Post', 'prio' => 3],
            ['type_id' => 'value', 'type_name' => 'prio', 'name' => 'sort', 'label' => 'Sortierung',
                'fields' => 'author_name', 'prio' => 4],
        ]);

        $this->buildTable('post', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'Titel', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'textarea', 'name' => 'body', 'label' => 'Text', 'prio' => 2],
            ['type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'author_id', 'label' => 'Autor',
                'table' => $this->t('author'), 'field' => 'name', 'type' => 0, 'empty_option' => 1, 'prio' => 3],
            ['type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'tags', 'label' => 'Tags',
                'table' => $this->t('tag'), 'field' => 'title', 'type' => 3, 'empty_option' => 1,
                'relation_table' => $this->t('post_tag'), 'prio' => 4],
            ['type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'related_ids', 'label' => 'Verwandt',
                'table' => $this->t('post'), 'field' => 'title', 'type' => 1, 'empty_option' => 1, 'prio' => 5],
            ['type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'comments_inline', 'label' => 'Kommentare inline',
                'table' => $this->t('comment'), 'field' => 'post_id', 'type' => 5, 'prio' => 6],
            ['type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'comments_popup', 'label' => 'Kommentare popup',
                'table' => $this->t('comment'), 'field' => 'post_id', 'type' => 4, 'prio' => 7],
        ]);

    }

    public function setUp(): void
    {
        $this->seed();
    }

    private function dropTable(string $tableName): void
    {
        try {
            Api::removeTable($tableName);
        } catch (Throwable) {
        }
        Sql::factory()->setQuery('DROP TABLE IF EXISTS `' . $tableName . '`');
    }

    /** @param list<array<string, mixed>> $fields */
    private function buildTable(string $short, array $fields): void
    {
        $tableName = $this->tables[$short];

        Api::setTable([
            'table_name' => $tableName,
            'name' => 'Rel ' . $short,
            'status' => 1,
            'hidden' => 1,
            'search' => 1,
            'list_amount' => 50,
        ], $fields);
        Table::deleteCache();

        // setTable() generates the schema before the fields exist — same as in
        // REDAXO 5, where importTablesets() re-runs the generator for this reason.
        $table = Table::get($tableName);
        if ($table) {
            Api::generateTableAndFields($table);
        }
        Table::deleteCache();
    }

    private function seed(): void
    {
        foreach (['post_tag', 'comment', 'post', 'tag', 'author'] as $short) {
            Sql::factory()->setQuery('DELETE FROM `' . $this->tables[$short] . '`');
        }
        Table::deleteCache();

        $this->authorIds = [];
        foreach ([['Ada', 'ada@example.com'], ['Alan', 'alan@example.com'], ['Grace', 'grace@example.com']] as [$name, $mail]) {
            $a = Dataset::create($this->t('author'));
            $a->setValue('name', $name);
            $a->setValue('email', $mail);
            $a->save();
            $this->authorIds[] = $a->getId();
        }

        $this->tagIds = [];
        foreach (['PHP', 'REDAXO', 'YForm'] as $title) {
            $x = Dataset::create($this->t('tag'));
            $x->setValue('title', $title);
            $x->save();
            $this->tagIds[] = $x->getId();
        }

        $this->postIds = [];
        for ($i = 1; $i <= 4; ++$i) {
            $p = Dataset::create($this->t('post'));
            $p->setValue('title', 'Beitrag ' . $i);
            $p->setValue('body', 'Inhalt ' . $i);
            $p->setValue('author_id', $this->authorIds[($i - 1) % count($this->authorIds)]);
            $p->save();
            $this->postIds[] = $p->getId();
        }

        // comma-list relation on post 1
        $first = Dataset::get($this->postIds[0], $this->t('post'));
        $first->setValue('related_ids', $this->postIds[1] . ',' . $this->postIds[2]);
        $first->save();

        // n:m — post 1 gets PHP + REDAXO, post 4 gets PHP + YForm
        foreach ([[0, 0], [0, 1], [3, 0], [3, 2]] as [$pi, $ti]) {
            $j = Dataset::create($this->t('post_tag'));
            $j->setValue('post_id', $this->postIds[$pi]);
            $j->setValue('tag_id', $this->tagIds[$ti]);
            $j->save();
        }

        // 1-n children on post 1
        foreach ([['Leser 1', 'Erster Kommentar'], ['Leser 2', 'Zweiter Kommentar']] as $n => [$who, $body]) {
            $c = Dataset::create($this->t('comment'));
            $c->setValue('author_name', $who);
            $c->setValue('body', $body);
            $c->setValue('post_id', $this->postIds[0]);
            $c->setValue('sort', $n + 1);
            $c->save();
        }

        Table::deleteCache();
    }

    /** @return list<array<string, mixed>> */
    protected function children(int $postId): array
    {
        return Sql::factory()->getArray(
            'SELECT id, author_name, body, sort FROM `' . $this->t('comment') . '` WHERE post_id = ' . $postId . ' ORDER BY sort, id',
        );
    }

    /**
     * Runs the real backend edit form for a post. The callback receives a map of
     * field name => numeric field id, because that is how the form addresses its
     * inputs when real_field_names is off (the backend default).
     *
     * @param callable(array<string, int|string>): array<int|string, mixed> $buildPost
     * @return list<string> warning messages
     */
    protected function submitPost(int $postId, callable $buildPost): array
    {
        $probeDataset = Dataset::get($postId, $this->t('post'));
        $probe = $probeDataset->getForm();
        $probe->setObjectparams('csrf_protection', false);
        $probe->setObjectparams('form_exit', false);
        $probe->setObjectparams('form_showformafterupdate', 1);

        // The relation widgets echo part of their markup instead of returning it —
        // keep that out of the test output.
        ob_start();
        try {
            $probeDataset->executeForm($probe);
        } finally {
            ob_end_clean();
        }

        $idByName = [];
        foreach ($probe->objparams['values'] ?? [] as $value) {
            $idByName[$value->getName()] = $value->getId();
        }

        $dataset = Dataset::get($postId, $this->t('post'));
        $yform = $dataset->getForm();
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('form_exit', false);
        $yform->setObjectparams('form_showformafterupdate', 1);
        $formName = $yform->getObjectparams('form_name');

        $post = $buildPost($idByName);
        $post['send'] = '1';

        $previousPost = $_POST['FORM'] ?? null;
        $previousRequest = $_REQUEST['FORM'] ?? null;
        $_POST['FORM'] = [$formName => $post];
        $_REQUEST['FORM'] = $_POST['FORM'];

        ob_start();
        try {
            $dataset->executeForm($yform);
        } finally {
            ob_end_clean();
            if (null === $previousPost) {
                unset($_POST['FORM'], $_REQUEST['FORM']);
            } else {
                $_POST['FORM'] = $previousPost;
                $_REQUEST['FORM'] = $previousRequest;
            }
        }

        Table::deleteCache();

        return array_values(array_filter(
            array_map(static fn ($m) => (string) $m, $yform->objparams['warning_messages'] ?? []),
            static fn (string $m) => '' !== $m,
        ));
    }

    protected function addon(): YFormAddon
    {
        /** @var YFormAddon $addon */
        $addon = Addon::require('yform');

        return $addon;
    }

    /**
     * Runs $fn with the request looking like a relation popup on the comment table.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    protected function popupRequest(callable $fn): mixed
    {
        return $this->withRequest([
            'page' => 'yform/manager/data_edit',
            'table_name' => $this->t('comment'),
            'rex_yform_manager_popup' => '1',
        ], $fn);
    }

    /**
     * Runs $fn with the request looking like a normal data-list call.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    protected function plainRequest(callable $fn): mixed
    {
        return $this->withRequest([
            'page' => 'yform/manager/data_edit',
            'table_name' => $this->t('comment'),
        ], $fn);
    }

    /**
     * Runs $fn with the request pointing at a yform page that is not a data list.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    protected function otherPageRequest(callable $fn): mixed
    {
        return $this->withRequest(['page' => 'yform/manager/table_edit'], $fn);
    }

    /**
     * @param array<string, string> $params
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    private function withRequest(array $params, callable $fn): mixed
    {
        $keys = ['page', 'table_name', 'rex_yform_manager_popup', 'rex_yform_manager_opener'];
        $previous = [];
        foreach ($keys as $key) {
            $previous[$key] = [$_GET[$key] ?? null, $_REQUEST[$key] ?? null];
            unset($_GET[$key], $_REQUEST[$key]);
        }

        foreach ($params as $key => $value) {
            $_GET[$key] = $value;
            $_REQUEST[$key] = $value;
        }

        // getPages() only emits the per-table entries for a logged-in backend user.
        $previousUser = Core::getUser();
        if (null === $previousUser) {
            Core::setProperty('user', User::require(1));
        }

        // Authorization caches the per-table grants per user and never invalidates
        // them. A suite that ran earlier may have filled the cache before this
        // suite's tables existed, and getPages() would then skip them.
        Authorization::$tableAuthorizations = null;
        Table::deleteCache();

        try {
            return $fn();
        } finally {
            if (null === $previousUser) {
                Core::setProperty('user', null);
            }
            foreach ($keys as $key) {
                [$get, $request] = $previous[$key];
                if (null === $get) {
                    unset($_GET[$key]);
                } else {
                    $_GET[$key] = $get;
                }
                if (null === $request) {
                    unset($_REQUEST[$key]);
                } else {
                    $_REQUEST[$key] = $request;
                }
            }
        }
    }

    /**
     * The page and all its subpages, depth first.
     *
     * @return list<Page>
     */
    protected function flatten(Page $page): array
    {
        $pages = [$page];
        foreach ($page->getSubpages() as $subpage) {
            $pages = array_merge($pages, $this->flatten($subpage));
        }

        return $pages;
    }

    /**
     * The page's explicitly set active flag, or null when it was never set.
     *
     * Page::isActive() falls back to comparing against the current backend page,
     * which does not exist under the console. What matters for the popup bug is
     * only whether getPages() set the flag itself.
     */
    protected function explicitIsActive(Page $page): ?bool
    {
        $prop = new ReflectionProperty(Page::class, 'isActive');

        return $prop->getValue($page);
    }

    private function trackFixture(string $tableName): void
    {
        $reflection = new ReflectionClass($this->fixtures);
        $prop = $reflection->getProperty('createdTables');
        $list = (array) $prop->getValue($this->fixtures);
        if (!in_array($tableName, $list, true)) {
            $list[] = $tableName;
            $prop->setValue($this->fixtures, $list);
        }
    }
}
