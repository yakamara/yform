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

// REST

rex_sql_table::get(rex::getTable('yform_rest_token'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('token', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('status', 'tinyint(1)', false, '1'))
    ->ensureColumn(new rex_sql_column('amount', 'int(11)'))
    ->ensureColumn(new rex_sql_column('interval', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('paths', 'text'))
    ->ensure();

rex_sql_table::get(rex::getTable('yform_rest_token_access'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('token_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('datetime_created', 'datetime'))
    ->ensureColumn(new rex_sql_column('url', 'text'))
    ->ensure();

// Manager

$table = rex_sql_table::get(rex::getTable('yform_table'));
$hasMassDeletion = $table->hasColumn('mass_deletion');
$hasMassEdit = $table->hasColumn('mass_edit');
$addon = rex_addon::get('yform');

$table
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('status', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('description', 'text'))
    ->ensureColumn(new rex_sql_column('table_icon', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('list_amount', 'int(11)', false, '50'))
    ->ensureColumn(new rex_sql_column('list_sortfield', 'varchar(191)', false, 'id'))
    ->ensureColumn(new rex_sql_column('list_sortorder', 'enum(\'ASC\',\'DESC\')', false, 'ASC'))
    ->ensureColumn(new rex_sql_column('prio', 'int(11)'))
    ->ensureColumn(new rex_sql_column('search', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('hidden', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('export', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('import', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('mass_deletion', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('mass_edit', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('schema_overwrite', 'tinyint(1)', false, '1'))
    ->ensureColumn(new rex_sql_column('history', 'tinyint(1)'))
    ->ensureIndex(new rex_sql_index('table_name', ['table_name'], rex_sql_index::UNIQUE))
    ->ensure();

rex_sql_table::get(rex::getTable('yform_field'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('prio', 'int(11)'))
    ->ensureColumn(new rex_sql_column('type_id', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('type_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('db_type', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('list_hidden', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('search', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('name', 'text'))
    ->ensureColumn(new rex_sql_column('label', 'text'))
    ->ensureColumn(new rex_sql_column('not_required', 'text'))
    ->ensureColumn(new rex_sql_column('multiple', 'text'))
    ->ensureColumn(new rex_sql_column('expanded', 'text'))
    ->ensureColumn(new rex_sql_column('choices', 'text'))
    ->ensureColumn(new rex_sql_column('choice_attributes', 'text'))
    ->ensure();

rex_sql_table::get(rex::getTable('yform_history'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('dataset_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('action', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('user', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('timestamp', 'datetime'))
    ->ensureIndex(new rex_sql_index('dataset', ['table_name', 'dataset_id']))
    ->ensure();

rex_sql_table::get(rex::getTable('yform_history_field'))
    ->ensureColumn(new rex_sql_column('history_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('field', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('value', 'longtext'))
    ->setPrimaryKey(['history_id', 'field'])
    ->ensure();

if (!$hasMassDeletion) {
    rex_sql::factory()
        ->setTable(rex::getTable('yform_table'))
        ->setValue('mass_deletion', 1)
        ->update();
}

if (!$hasMassEdit) {
    rex_sql::factory()
        ->setTable(rex::getTable('yform_table'))
        ->setValue('mass_edit', 1)
        ->update();
}

$c = rex_sql::factory();
$c->setQuery(
    'ALTER TABLE `' . rex::getTable('yform_table') . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
);
$c->setQuery(
    'ALTER TABLE `' . rex::getTable('yform_field') . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
);
$c->setQuery(
    'ALTER TABLE `' . rex::getTable('yform_history') . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
);
$c->setQuery(
    'ALTER TABLE `' . rex::getTable(
        'yform_history_field',
    ) . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
);

// from 4.0 on, but on every reinstall
if ($this->isInstalled()) {
    foreach (rex_sql::factory()->getArray('SELECT id, perms FROM ' . rex::getTablePrefix() . 'user_role') as $role) {
        if (!str_contains((string) $role['perms'], '"yform_manager_table_edit":')) {
            $perms = str_replace('"yform_manager_table":', '"yform_manager_table_edit":', (string) $role['perms']);
            rex_sql::factory()->setQuery(
                'UPDATE ' . rex::getTablePrefix() . 'user_role SET perms=? where id=?',
                [$perms, $role['id']],
            );
        }
    }

    try {
        rex_sql::factory()->setQuery(
            'UPDATE ' . rex::getTable(
                'yform_field',
            ) . ' SET `format` = "" WHERE type_name = "datestamp" AND `format` = "mysql"',
        );
    } catch (rex_sql_exception $e) {
    }

    foreach (rex_sql::factory()->getArray('select * from `' . rex::getTable('yform_field') . '`') as $field) {
        if ('value' == $field['type_id']) {
            switch ($field['type_name']) {
                case 'be_media_category':
                case 'be_select_category':
                case 'remembervalues':
                case 'mediafile':
                case 'captcha':
                case 'captcha_calc':
                case 'recaptcha':
                case 'recaptcha_v3':
                    // remove these fields
                    rex_sql::factory()->setQuery(
                        'delete from `' . rex::getTable('yform_field') . '` where id = :id',
                        ['id' => $field['id']],
                    );
                    break;

                case 'labelexist':
                    // rename
                    $field_new_name = 'labelexist';
                    $field_old_name = 'in_names';
                    try {
                        rex_sql::factory()->setQuery(
                            'update ' . rex::getTable(
                                'yform_field',
                            ) . ' set type_name = ? where type_id="value" and type_name = ?',
                            [$field_new_name, $field_old_name],
                        );
                        rex_sql::factory()->setQuery(
                            'update ' . rex::getTable('yform_history_field') . ' set field = ? where field = ?',
                            [$field_new_name, $field_old_name],
                        );
                    } catch (rex_sql_exception $e) {
                    }
                    break;

                case 'existintable':
                    // rename
                    $field_new_name = 'existintable';
                    $field_old_name = 'in_table';
                    try {
                        rex_sql::factory()->setQuery(
                            'update ' . rex::getTable(
                                'yform_field',
                            ) . ' set type_name = ? where type_id="value" and type_name = ?',
                            [$field_new_name, $field_old_name],
                        );
                        rex_sql::factory()->setQuery(
                            'update ' . rex::getTable('yform_history_field') . ' set field = ? where field = ?',
                            [$field_new_name, $field_old_name],
                        );
                    } catch (rex_sql_exception $e) {
                    }
                    break;

                case 'float':
                    // change to number
                    rex_sql::factory()->setQuery(
                        'update `' . rex::getTable('yform_field') . '` set
                        type_name = "number", db_type = "", `default` = ""
                        where id = :id',
                        ['id' => $field['id']],
                    );
                    break;

                case 'select':
                    // change to choice
                    rex_sql::factory()->setQuery(
                        'update `' . rex::getTable('yform_field') . '` set
                        type_name = "choice",
                        db_type = "text",
                        expanded = 0,
                        choices = :choices,
                        choice_attributes = :choice_attributes
                        where id = :id',
                        [
                            'id' => $field['id'],
                            'choices' => $field['options'],
                            'choice_attributes' => $field['attributes'] ?? '',
                        ],
                    );
                    break;

                case 'radio':
                    // change to choice
                    rex_sql::factory()->setQuery(
                        'update `' . rex::getTable('yform_field') . '` set
                        type_name = "choice",
                        db_type = "text",
                        expanded = 1,
                        multiple = 0,
                        choices = :choices,
                        choice_attributes = :choice_attributes
                        where id = :id',
                        [
                            'id' => $field['id'],
                            'choices' => $field['options'],
                            'choice_attributes' => $field['attributes'] ?? '',
                        ],
                    );
                    break;

                case 'checkbox_sql':
                    // change to choice
                    rex_sql::factory()->setQuery(
                        'update `' . rex::getTable('yform_field') . '` set
                        type_name = "choice",
                        db_type = "text",
                        expanded = 1,
                        multiple = 1,
                        choices = :choices
                        where id = :id',
                        [
                            'id' => $field['id'],
                            'choices' => $field['query'] ?? '',
                        ],
                    );
                    break;

                case 'radio_sql':
                    // change to choice
                    rex_sql::factory()->setQuery(
                        'update `' . rex::getTable('yform_field') . '` set
                        type_name = "choice",
                        db_type = "text",
                        expanded = 1,
                        multiple = 0,
                        choices = :choices,
                        choice_attributes = :choice_attributes
                        where id = :id',
                        [
                            'id' => $field['id'],
                            'choices' => $field['query'] ?? '',
                            'choice_attributes' => $field['attributes'] ?? '',
                        ],
                    );
                    break;

                case 'select_sql':
                    // change to choice
                    rex_sql::factory()->setQuery(
                        'update `' . rex::getTable('yform_field') . '` set
                        type_name = "choice",
                        db_type = "text",
                        expanded = 0,
                        choices = :choices,
                        choice_attributes = :choice_attributes
                        where id = :id',
                        [
                            'id' => $field['id'],
                            'choices' => $field['query'] ?? '',
                            'choice_attributes' => $field['attributes'] ?? '',
                        ],
                    );
                    break;
                case 'password':
                    // change to text
                    rex_sql::factory()->setQuery(
                        'update `' . rex::getTable('yform_field') . '` set
                        type_name = "text",
                        where id = :id',
                        [
                            'id' => $field['id'],
                        ],
                    );
                    break;
            }
        }
    }
}

if (class_exists('rex_yform_manager_table')) {
    rex_yform_manager_table::deleteCache();
}
