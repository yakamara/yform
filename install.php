<?php

/**
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

try {
    // transaction test.
    $testSQL = rex_sql::factory();
    $testSQL->transactional(static function () {
        rex_sql::factory()->setQuery('SELECT * from rex_user LIMIT 1');
    });
} catch (Exception $e) {
    throw new rex_exception('db does not support transactions: ' . $e->getMessage(), $e);
}

// old plugin docs still exists ? -> delete
$pluginDocs = __DIR__ . '/plugins/docs';
if (file_exists($pluginDocs)) {
    rex_dir::delete($pluginDocs);
}

foreach ($this->getInstalledPlugins() as $plugin) {
    // use path relative to __DIR__ to get correct path in update temp dir
    $file = __DIR__ . '/plugins/' . $plugin->getName() . '/install.php';

    if (file_exists($file)) {
        $plugin->includeFile($file);
    }
}

rex_dir::delete($this->getDataPath('fonts'));

rex_autoload::removeCache();

// E-Mail

rex_sql_table::get(rex::getTable('yform_email_template'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('mail_from', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('mail_from_name', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('mail_reply_to', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('mail_reply_to_name', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('subject', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('body', 'text'))
    ->ensureColumn(new rex_sql_column('body_html', 'text'))
    ->ensureColumn(new rex_sql_column('attachments', 'text'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->ensureIndex(new rex_sql_index('name', ['name'], rex_sql_index::UNIQUE))
    ->ensure();

$c = rex_sql::factory();
$c->setQuery('ALTER TABLE `' . rex::getTable('yform_email_template') . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
