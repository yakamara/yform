<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_createdb extends rex_yform_action_abstract
{

    function executeAction()
    {
        $table = $this->getElement(2);

        // Tabelle vorhanden ?
        $sql = rex_sql::factory();
        $sql->debugsql = 1;
        $sql->setQuery('show tables');
        $table_exists = false;
        foreach ($sql->getArray() as $k => $v) {
            if ($table == $v) {
                $table_exists = true;
                break;
            }
        }

        if (!$table_exists) {
            $sql->setQuery('CREATE TABLE `' . $table . '` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY);');
        }

        // Welche Felder sind vorhanden ?
        $sql->setQuery('show columns from ' . $table);
        $sql_cols = $sql->getArray();
        $cols = array();
        foreach ($sql_cols as $k => $v) {
            $cols[] = $v['Field'];
        }

        // wenn Feld nicht in Datenbank, dann als TEXT anlegen.
        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if (!in_array($key, $cols)) {
                $sql->setQuery('ALTER TABLE `' . $table . '` ADD `' . $key . '` TEXT NOT NULL;');
            }
        }

        return;

    }

    function getDescription()
    {
        return 'action|createdb|tblname';
    }

}
