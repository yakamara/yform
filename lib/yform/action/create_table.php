<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_create_table extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        $tableName = $this->getElement(2);
        $tableExists = true;

        $table = rex_yform_manager_table::get($tableName);
        if (null === $table) {
            $tableExists = false;
        }

        if (!$tableExists) {
            $yform = new rex_yform();
            $yform->setObjectparams('send', '1');
            $yform->setObjectparams('csrf_protection', '0');
            $yform->setObjectparams('main_table', rex_yform_manager_table::table());
            $yform->setValueField('hidden', ['table_name', $tableName]);
            $yform->setValueField('hidden', ['name', $tableName]);
            $yform->setValueField('hidden', ['status', '1']);
            $yform->setActionField('db', [rex_yform_manager_table::table()]);
            $form = $yform->getForm();

            rex_yform_manager_table::deleteCache();
            $table = rex_yform_manager_table::get($tableName);
            if ($table) {
                rex_yform_manager_table_api::generateTableAndFields($table);
            }
        }

        rex_yform_manager_table::deleteCache();
        $table = rex_yform_manager_table::get($tableName);

        if ($table) {
            $migrateTable = false;
            $columns = $table->getColumns();
            foreach ($this->getObjects() as $object) {
                if ('' === $object->getName() || 'objparams' === $object->type) {
                    continue;
                }
                if (!isset($columns[$object->getName()])) {
                    $dbType = 'TEXT';

                    $class = 'rex_yform_value_'.$object->type;
                    /** @var rex_yform_base_abstract $cl */
                    $cl = new $class();
                    $definitions = $cl->getDefinitions();

                    if (!isset($definitions['type']) || 'value' !== $definitions['type']) {
                        continue;
                    }

                    if (isset($definitions['db_type']) && isset($definitions['db_type'][0])) {
                        $dbType = $definitions['db_type'][0];
                    }

                    if ('none' !== $dbType) {
                        $migrateTable = true;
                        $sql = rex_sql::factory();
                        $sql->setQuery('ALTER TABLE `'.$tableName.'` ADD `'.$object->getName().'` '.$dbType.' NOT NULL;');
                    }
                }
            }
            if ($migrateTable) {
                rex_yform_manager_table_api::migrateTable($tableName);
            }
        }
    }

    public function getDescription(): string
    {
        return 'action|create_table|tablename';
    }
}
