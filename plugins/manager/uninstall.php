<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$sql = rex_sql::factory();
$sql->setQuery('DROP TABLE IF EXISTS `' . rex::getTablePrefix() . 'yform_table`;');
$sql->setQuery('DROP TABLE IF EXISTS `' . rex::getTablePrefix() . 'yform_field`;');
$sql->setQuery('DROP TABLE IF EXISTS `' . rex::getTablePrefix() . 'yform_history`;');
$sql->setQuery('DROP TABLE IF EXISTS `' . rex::getTablePrefix() . 'yform_history_field`;');
