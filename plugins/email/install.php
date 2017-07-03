<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$sql = rex_sql::factory();
$sql->setQuery('CREATE TABLE IF NOT EXISTS `' . rex::getTablePrefix() . 'yform_email_template` (
    `id` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL default "",
    `mail_from` varchar(255) NOT NULL default "",
    `mail_from_name` varchar(255) NOT NULL default "",
    `subject` varchar(255) NOT NULL default "",
    `body` text NOT NULL,
    `body_html` text NOT NULL,
    `attachments` TEXT NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
