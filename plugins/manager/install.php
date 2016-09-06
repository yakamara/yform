<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$sql = rex_sql::factory();

$sql->setQuery('CREATE TABLE IF NOT EXISTS `' . rex::getTablePrefix() . 'yform_table` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `status` tinyint(1) NOT NULL,
    `table_name` varchar(100) NOT NULL,
    `name` varchar(100) NOT NULL,
    `description` text NOT NULL,
    `list_amount` tinyint(3) unsigned NOT NULL DEFAULT 50,
    `list_sortfield` VARCHAR(255) NOT NULL DEFAULT "id",
    `list_sortorder` ENUM("ASC","DESC") NOT NULL DEFAULT "ASC",
    `prio` int(11) NOT NULL,
    `search` tinyint(1) NOT NULL,
    `hidden` tinyint(1) NOT NULL,
    `export` tinyint(1) NOT NULL,
    `import` tinyint(1) NOT NULL,
    `mass_deletion` tinyint(1) NOT NULL,
    `history` tinyint(1) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE(`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

$sql->setQuery('CREATE TABLE IF NOT EXISTS `' . rex::getTablePrefix() . 'yform_field` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `table_name` varchar(100) NOT NULL,
    `prio` int(11) NOT NULL,
    `type_id` varchar(100) NOT NULL,
    `type_name` varchar(100) NOT NULL,
    `list_hidden` tinyint(1) NOT NULL,
    `search` tinyint(1) NOT NULL,
    `name` text NOT NULL,
    `label` text NOT NULL,
    `not_required` TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

$sql->setQuery('CREATE TABLE IF NOT EXISTS `' . rex::getTablePrefix() . 'yform_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `table_name` varchar(255) NOT NULL,
    `dataset_id` int(11) NOT NULL,
    `action` varchar(255) NOT NULL,
    `user` varchar(255) NOT NULL,
    `timestamp` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `dataset` (`table_name`, `dataset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

$sql->setQuery('CREATE TABLE IF NOT EXISTS `' . rex::getTablePrefix() . 'yform_history_field` (
    `history_id` int(11) NOT NULL,
    `field` varchar(255) NOT NULL,
    `value` longtext NOT NULL,
    PRIMARY KEY (`history_id`, `field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

$table = rex_sql_table::get(rex::getTable('yform_table'));
$hasMassDeletion = $table->hasColumn('mass_deletion');
$table
    ->ensureColumn(new rex_sql_column('mass_deletion', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('history', 'tinyint(1)'))
    ->alter();

if (!$hasMassDeletion) {
    $sql
        ->setTable(rex::getTable('yform_table'))
        ->setValue('mass_deletion', 1)
        ->update();
}
