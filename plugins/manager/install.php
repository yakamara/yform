<?php


rex_sql_util::importDump($this->getPath('_install.sql'));





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
    PRIMARY KEY (`id`),
    UNIQUE(`table_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;');

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;');
