<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\YForm;

use function count;
use function in_array;

/**
 * Covers every be_manager_relation mode against a real four-table fixture:
 *
 *   type 0  Single (select)      n:1, own FK column
 *   type 1  Multiple (select)    n:m as a comma list in one column
 *   type 2  Single (popup)       like 0, popup UI
 *   type 3  Multiple (popup)     n:m through a join table
 *   type 4  1-n (popup)          FK lives in the *target* table
 *   type 5  1-n (inline)         same wiring, children edited inside the parent form
 *
 * The inline mode is exercised through the real backend form, because that is
 * what maintains the child rows and their prio ordering.
 *
 * @package redaxo\yform
 * @internal
 */
final class RelationsSuite extends AbstractRelationFixture
{
    // ---------- reading through the ORM ----------

    public function testSingleSelectResolvesRelatedDataset(): void
    {
        $post = Dataset::get($this->postIds[0], $this->t('post'));

        $this->assertSame($this->authorIds[0], (int) $post->getValue('author_id'));
        $this->assertSame('Ada', $post->getRelatedDataset('author_id')?->getValue('name'));
    }

    public function testCommaListRelationResolvesCollection(): void
    {
        $post = Dataset::get($this->postIds[0], $this->t('post'));
        $related = $post->getRelatedCollection('related_ids');

        $this->assertNotNull($related);
        $this->assertCount(2, $related);

        $ids = [];
        foreach ($related as $item) {
            $ids[] = $item->getId();
        }
        sort($ids);
        $expected = [$this->postIds[1], $this->postIds[2]];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    public function testJoinTableRelationResolvesCollection(): void
    {
        $post = Dataset::get($this->postIds[0], $this->t('post'));

        $titles = [];
        foreach ($post->getRelatedCollection('tags') ?? [] as $tag) {
            $titles[] = $tag->getValue('title');
        }
        sort($titles);

        $this->assertSame(['PHP', 'REDAXO'], $titles);
    }

    public function testOneToManyChildrenAreFoundByForeignKey(): void
    {
        $children = Dataset::query($this->t('comment'))
            ->where('post_id', $this->postIds[0])
            ->find();

        $this->assertCount(2, $children);
    }

    public function testOneToManyRelationHasNoOwnColumn(): void
    {
        // type 4 and 5 keep the foreign key in the target table, so the parent
        // must not grow a column of its own — same for the join-table mode.
        $columns = array_column(Sql::showColumns($this->t('post')), 'name');

        $this->assertFalse(in_array('comments_inline', $columns, true));
        $this->assertFalse(in_array('comments_popup', $columns, true));
        $this->assertFalse(in_array('tags', $columns, true));
        $this->assertTrue(in_array('author_id', $columns, true));
        $this->assertTrue(in_array('related_ids', $columns, true));
    }

    // ---------- writing through the backend form ----------

    public function testFormSaveRewritesEveryRelationKind(): void
    {
        $this->submitPost($this->postIds[0], fn (array $id) => [
            $id['title'] => 'Bearbeitet',
            $id['author_id'] => (string) $this->authorIds[1],
            $id['tags'] => [(string) $this->tagIds[2]],
            $id['related_ids'] => [(string) $this->postIds[3]],
        ]);

        Table::deleteCache();
        $fresh = Dataset::get($this->postIds[0], $this->t('post'));

        $this->assertSame('Bearbeitet', $fresh->getValue('title'));
        $this->assertSame($this->authorIds[1], (int) $fresh->getValue('author_id'));
        $this->assertSame((string) $this->postIds[3], (string) $fresh->getValue('related_ids'));

        $tagIds = array_map(
            static fn (array $r) => (int) $r['tag_id'],
            Sql::factory()->getArray('SELECT tag_id FROM `' . $this->t('post_tag') . '` WHERE post_id = ' . $this->postIds[0]),
        );
        $this->assertSame([$this->tagIds[2]], $tagIds);
    }

    public function testFormSaveCanClearRelations(): void
    {
        $this->submitPost($this->postIds[0], fn (array $id) => [
            $id['title'] => 'Ohne Relationen',
            $id['author_id'] => '',
            $id['tags'] => [],
            $id['related_ids'] => [],
        ]);

        Table::deleteCache();
        $fresh = Dataset::get($this->postIds[0], $this->t('post'));

        $this->assertSame('', (string) $fresh->getValue('related_ids'));
        $this->assertCount(
            0,
            Sql::factory()->getArray('SELECT id FROM `' . $this->t('post_tag') . '` WHERE post_id = ' . $this->postIds[0]),
        );
    }

    /**
     * Guards the empty_option contract: without it a relation is mandatory and
     * clearing it has to produce a field warning instead of silently saving.
     */
    public function testRelationWithoutEmptyOptionRejectsEmptyValue(): void
    {
        $tableName = $this->t('post');
        Api::setTableField($tableName, [
            'type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'author_id',
            'label' => 'Autor', 'table' => $this->t('author'), 'field' => 'name', 'type' => 0,
            'empty_option' => 0, 'empty_value' => 'Bitte einen Autor waehlen', 'prio' => 3,
        ]);
        Table::deleteCache();

        try {
            $warnings = $this->submitPost($this->postIds[1], fn (array $id) => [
                $id['title'] => 'Ohne Autor',
                $id['author_id'] => '',
                $id['tags'] => [],
                $id['related_ids'] => [],
            ]);

            $this->assertTrue(
                in_array('Bitte einen Autor waehlen', $warnings, true),
                'expected the empty_value message, got ' . json_encode($warnings),
            );
        } finally {
            Api::setTableField($tableName, [
                'type_id' => 'value', 'type_name' => 'be_manager_relation', 'name' => 'author_id',
                'label' => 'Autor', 'table' => $this->t('author'), 'field' => 'name', 'type' => 0,
                'empty_option' => 1, 'empty_value' => '', 'prio' => 3,
            ]);
            Table::deleteCache();
        }
    }

    // ---------- inline 1-n ----------

    public function testInlineRelationUpdatesExistingChild(): void
    {
        $children = $this->children($this->postIds[0]);
        $this->assertCount(2, $children);

        $this->submitPost($this->postIds[0], fn (array $id) => [
            $id['title'] => 'Inline Update',
            $id['author_id'] => '',
            $id['tags'] => [],
            $id['related_ids'] => [],
            $id['comments_inline'] => [
                0 => ['id' => (string) $children[0]['id'], 1 => 'Geaendert', 2 => 'Neuer Text', 3 => (string) $this->postIds[0], 'send' => '1'],
                1 => ['id' => (string) $children[1]['id'], 1 => $children[1]['author_name'], 2 => $children[1]['body'], 3 => (string) $this->postIds[0], 'send' => '1'],
            ],
        ]);

        $after = $this->children($this->postIds[0]);
        $this->assertCount(2, $after);
        $this->assertSame('Geaendert', $after[0]['author_name']);
        $this->assertSame('Neuer Text', $after[0]['body']);
    }

    /**
     * A row without an `id` key is a new child — that is exactly what the JS
     * prototype in value.be_manager_inline_relation.tpl.php emits.
     */
    public function testInlineRelationCreatesChildWithoutIdKey(): void
    {
        $children = $this->children($this->postIds[0]);

        $this->submitPost($this->postIds[0], fn (array $id) => [
            $id['title'] => 'Inline Insert',
            $id['author_id'] => '',
            $id['tags'] => [],
            $id['related_ids'] => [],
            $id['comments_inline'] => [
                0 => ['id' => (string) $children[0]['id'], 1 => $children[0]['author_name'], 2 => $children[0]['body'], 3 => (string) $this->postIds[0], 'send' => '1'],
                1 => ['id' => (string) $children[1]['id'], 1 => $children[1]['author_name'], 2 => $children[1]['body'], 3 => (string) $this->postIds[0], 'send' => '1'],
                2 => [1 => 'Ganz neu', 2 => 'Frisch', 3 => (string) $this->postIds[0], 'send' => '1'],
            ],
        ]);

        $after = $this->children($this->postIds[0]);
        $this->assertCount(3, $after);
        $this->assertTrue(in_array('Ganz neu', array_column($after, 'author_name'), true));
    }

    public function testInlineRelationRenumbersPrio(): void
    {
        $children = $this->children($this->postIds[0]);

        // Submit in reverse order — the prio field must follow the submitted order.
        $this->submitPost($this->postIds[0], fn (array $id) => [
            $id['title'] => 'Inline Sort',
            $id['author_id'] => '',
            $id['tags'] => [],
            $id['related_ids'] => [],
            $id['comments_inline'] => [
                0 => ['id' => (string) $children[1]['id'], 1 => $children[1]['author_name'], 2 => $children[1]['body'], 3 => (string) $this->postIds[0], 'send' => '1'],
                1 => ['id' => (string) $children[0]['id'], 1 => $children[0]['author_name'], 2 => $children[0]['body'], 3 => (string) $this->postIds[0], 'send' => '1'],
            ],
        ]);

        $after = $this->children($this->postIds[0]);
        $this->assertSame([1, 2], array_map('intval', array_column($after, 'sort')));
        $this->assertSame($children[1]['author_name'], $after[0]['author_name']);
    }

    /**
     * Documents a sharp edge rather than a defect: the inline field is
     * authoritative. A form posted without it deletes every child — identical
     * to REDAXO 5. Code that saves a subset of the fields must go through the
     * ORM, not through a hand-built form POST.
     */
    public function testInlineRelationOmittedFromPostDeletesChildren(): void
    {
        $this->assertCount(2, $this->children($this->postIds[0]));

        $this->submitPost($this->postIds[0], fn (array $id) => [
            $id['title'] => 'Ohne Inline-Daten',
            $id['author_id'] => '',
            $id['tags'] => [],
            $id['related_ids'] => [],
        ]);

        $this->assertCount(0, $this->children($this->postIds[0]));
    }

    public function testOrmSaveLeavesInlineChildrenAlone(): void
    {
        $this->assertCount(2, $this->children($this->postIds[0]));

        $post = Dataset::get($this->postIds[0], $this->t('post'));
        $post->setValue('title', 'Nur ORM');
        $this->assertTrue($post->save());

        $this->assertCount(2, $this->children($this->postIds[0]));
    }

    // ---------- search ----------

    public function testRelationSearchFilterFindsByRelatedId(): void
    {
        $field = Table::get($this->t('post'))->getValueField('author_id');
        $class = $field->getObject()::class;

        $query = Dataset::query($this->t('post'))->alias('t0');
        $query = $class::getSearchFilter([
            'value' => (string) $this->authorIds[0],
            'field' => $field,
            'query' => $query,
        ]);

        // Posts 1 and 4 were seeded with the first author.
        $this->assertCount(2, $query->find());
    }

    public function testJoinTableSearchFilterUsesExistsSubquery(): void
    {
        $field = Table::get($this->t('post'))->getValueField('tags');
        $class = $field->getObject()::class;

        $query = Dataset::query($this->t('post'))->alias('t0');
        $query = $class::getSearchFilter([
            'value' => (string) $this->tagIds[0],
            'field' => $field,
            'query' => $query,
        ]);

        // Tag 1 is attached to post 1 and post 4.
        $this->assertCount(2, $query->find());
    }

    // ---------- popup mode ----------

    /**
     * A relation field of type 2/3/4 opens the target table's data list as a popup.
     * setPopup() has to sit on the *main* page, never on the per-table page, because
     * it calls addItemClass() — which stores itemAttr['class'] as a string — and the
     * navigation fragment then runs `$item['itemAttr']['class'][] = 'active'` on it:
     * "[] operator not supported for strings", a 500 on every popup.
     *
     * REDAXO 5 got this right (boot.php set popup on the main page and isActive=false
     * with it); the first port of getPages() put it on the table page instead.
     */
    public function testPopupModeIsSetOnTheMainPageNotOnTheTablePage(): void
    {
        $pages = $this->popupRequest(fn () => iterator_to_array($this->addon()->getPages(), false));

        $main = null;
        $tablePage = null;
        foreach ($pages as $page) {
            if ('yform' === $page->getKey()) {
                $main = $page;
            }
            if ('yform_table_' . $this->t('comment') === $page->getKey()) {
                $tablePage = $page;
            }
        }

        $this->assertNotNull($main, 'main yform page missing');
        $this->assertNotNull($tablePage, 'per-table page missing');

        $this->assertTrue($main->isPopup(), 'the main page must carry popup mode');
        $this->assertFalse($this->explicitIsActive($main) ?? true, 'the main page must be marked inactive in popup mode');
        $this->assertFalse($main->hasNavigation(), 'popup mode must switch the navigation off');

        // isPopup() inherits from the parent, so the table page is a popup too —
        // but it must not have grown the string class attribute itself.
        $this->assertTrue(true === $this->explicitIsActive($tablePage), 'the requested table page must be active');
        $this->assertSame('', $tablePage->getItemAttr('class'), 'the active page must not carry an item class');
    }

    /**
     * The actual crash condition, checked over every page yform registers: a page
     * that is active must not carry a string `class` item attribute.
     */
    public function testNoActivePageCarriesAStringItemClass(): void
    {
        foreach ([true, false] as $asPopup) {
            $build = fn () => iterator_to_array($this->addon()->getPages(), false);
            $pages = $asPopup ? $this->popupRequest($build) : $this->plainRequest($build);

            foreach ($pages as $page) {
                foreach ($this->flatten($page) as $candidate) {
                    if (true !== $this->explicitIsActive($candidate)) {
                        continue;
                    }
                    $class = $candidate->getItemAttr('class');
                    $this->assertTrue(
                        '' === $class || is_array($class),
                        sprintf(
                            'page "%s" is active and has itemAttr[class] = %s — the navigation fragment appends to it with [] and would fail',
                            $candidate->getKey(),
                            var_export($class, true),
                        ),
                    );
                }
            }
        }
    }

    /**
     * A plain data list keeps its navigation. The main page is still marked inactive,
     * because the table has its own navigation entry and only one of the two should
     * light up — REDAXO 5 did the same with $main_page['isActive'] = false.
     */
    public function testNormalRequestIsNotAPopupButYieldsToTheTableEntry(): void
    {
        $pages = $this->plainRequest(fn () => iterator_to_array($this->addon()->getPages(), false));

        $main = null;
        $tablePage = null;
        foreach ($pages as $page) {
            if ('yform' === $page->getKey()) {
                $main = $page;
            }
            if ('yform_table_' . $this->t('comment') === $page->getKey()) {
                $tablePage = $page;
            }
        }

        $this->assertNotNull($main, 'main yform page missing');
        $this->assertFalse($main->isPopup(), 'a plain request must not be a popup');
        $this->assertTrue($main->hasNavigation(), 'a plain request keeps its navigation');
        $this->assertFalse($this->explicitIsActive($main) ?? true, 'the table entry is the active one, not the yform entry');

        $this->assertNotNull($tablePage);
        $this->assertTrue(true === $this->explicitIsActive($tablePage), 'the requested table page must be active');
    }

    /**
     * A request that is not a data list at all leaves the main page's active state to
     * core, which resolves it from the current page tree.
     */
    public function testRequestOutsideTheDataListLeavesActiveStateToCore(): void
    {
        $pages = $this->otherPageRequest(fn () => iterator_to_array($this->addon()->getPages(), false));

        foreach ($pages as $page) {
            if ('yform' === $page->getKey()) {
                $this->assertNull($this->explicitIsActive($page), 'the addon must not force an active state here');
                $this->assertFalse($page->isPopup());
                return;
            }
        }

        $this->assertTrue(false, 'main yform page missing');
    }
}
