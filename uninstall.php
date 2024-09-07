<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// E-Mail

rex_sql_table::get(rex::getTable('yform_email_template'))
    ->drop();

// REST

rex_sql_table::get(rex::getTable('yform_rest_token'))
    ->drop();
rex_sql_table::get(rex::getTable('yform_rest_token_access'))
    ->drop();

// Manager

rex_sql_table::get(rex::getTable('yform_table'))
    ->drop();
rex_sql_table::get(rex::getTable('yform_field'))
    ->drop();
rex_sql_table::get(rex::getTable('yform_history'))
    ->drop();
rex_sql_table::get(rex::getTable('yform_history_field'))
    ->drop();
