<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

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
