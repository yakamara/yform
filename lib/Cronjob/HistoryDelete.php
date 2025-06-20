<?php

namespace Redaxo\YForm\Cronjob;

use DateTime;
use Exception;
use rex;
use rex_cronjob;
use rex_i18n;
use rex_sql;
use rex_yform_manager_table;

class HistoryDelete extends rex_cronjob
{
    public function execute()
    {
        try {

            $table = $this->getParam('table', null);
            $interval = $this->getParam('interval', null);

            if (null === $table || null === $interval) {
                throw new Exception(rex_i18n::msg('yform_cronjob_history_delete_error_missing'));
            }

            $table = rex_yform_manager_table::get($table);
            if (!$table->hasHistory()) {
                throw new Exception(rex_i18n::msg('yform_cronjob_history_delete_error_table_not_history'));
            }

            switch ($interval) {
                case 'monthly':
                    $interval = '1 MONTH';
                    break;
                case 'three_months':
                    $interval = '3 MONTH';
                    break;
                case 'half_yearly':
                    $interval = '6 MONTH';
                    break;
                case 'interval_yearly':
                    $interval = '1 YEAR';
                    break;
                default:
                    throw new Exception(rex_i18n::msg('yform_cronjob_history_delete_error_interval_wrong'));
            }

            $DateTime = new DateTime();
            $DateTime->modify('-' . $interval);

            $Datasets = rex_sql::factory()
                ->getArray('SELECT id FROM ' . rex::getTable('yform_history') . ' WHERE table_name = ? and timestamp < ? LIMIT 10000', [$table->getTableName(), $DateTime->format('Y-m-d H:i:s')]);

            $item_count = 0;
            foreach ($Datasets as $Dataset) {
                $history_id = $Dataset['id'];
                ++$item_count;

                $sql = rex_sql::factory();
                $sql
                    ->setTable(rex::getTable('yform_history_field'))
                    ->setWhere('history_id = ?', [$history_id])
                    ->delete();

                $sql = rex_sql::factory();
                $sql
                    ->setTable(rex::getTable('yform_history'))
                    ->setWhere('id = ?', [$history_id])
                    ->delete();
            }

            $this->setMessage(rex_i18n::msg('yform_cronjob_history_delete_message', $item_count));
            return true;
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
            return false;
        }
    }

    public function getTypeName()
    {
        return rex_i18n::msg('yform_cronjob_history_delete');
    }

    public function getParamFields()
    {
        $tables = [];
        foreach (rex_yform_manager_table::getAll() as $table) {
            if ($table->hasHistory()) {
                $tables[$table->getTableName()] = $table->getTableName();
            }
        }

        return [
            [
                'label' => rex_i18n::msg('yform_cronjob_history_delete_tables'),
                'name' => 'table',
                'type' => 'select',
                'options' => $tables,
                'default' => '',
            ],
            [
                'label' => rex_i18n::msg('yform_cronjob_history_delete_interval'),
                'name' => 'interval',
                'type' => 'select',
                'options' => [
                    'monthly' => rex_i18n::msg('yform_cronjob_history_delete_interval_monthly'),
                    'three_months' => rex_i18n::msg('yform_cronjob_history_delete_interval_three_months'),
                    'half_yearly' => rex_i18n::msg('yform_cronjob_history_delete_interval_half_yearly'),
                    'interval_yearly' => rex_i18n::msg('yform_cronjob_history_delete_interval_yearly'),
                ],
                'default' => '',
            ],
        ];
    }
}
