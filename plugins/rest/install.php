<?php

rex_sql_table::get(rex::getTable('yform_rest_token'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('token', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('status', 'tinyint(1)', false, '1'))
    ->ensureColumn(new rex_sql_column('amount', 'int(11)'))
    ->ensureColumn(new rex_sql_column('interval', 'varchar(191)'))
    ->ensure();

rex_sql_table::get(rex::getTable('yform_rest_token_access'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('token_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('datetime_created', 'datetime'))
    ->ensureColumn(new rex_sql_column('url', 'text'))
    ->ensure();
