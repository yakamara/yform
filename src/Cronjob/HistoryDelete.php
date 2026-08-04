<?php

namespace Yakamara\YForm\Cronjob;

use Override;
use DateTime;
use Exception;
use Redaxo\Core\Core;
use Redaxo\Core\Cronjob\Type\AbstractType;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Manager\Table\Table;

class HistoryDelete extends AbstractType
{
    #[Override]
    public function execute(): bool
    {
        try {

            $tableName = $this->getParam('table', null);
            $interval = $this->getParam('interval', null);

            if (null === $tableName || null === $interval) {
                throw new Exception(I18n::msg('yform_cronjob_history_delete_error_missing'));
            }

            $table = Table::get($tableName);
            if (null === $table || !$table->hasHistory()) {
                throw new Exception(I18n::msg('yform_cronjob_history_delete_error_table_not_history'));
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
                    throw new Exception(I18n::msg('yform_cronjob_history_delete_error_interval_wrong'));
            }

            $DateTime = new DateTime();
            $DateTime->modify('-' . $interval);

            $Datasets = Sql::factory()
                ->getArray('SELECT id FROM ' . Core::getTable('yform_history') . ' WHERE table_name = ? and timestamp < ? LIMIT 10000', [$table->getTableName(), $DateTime->format('Y-m-d H:i:s')]);

            $item_count = 0;
            foreach ($Datasets as $Dataset) {
                $history_id = $Dataset['id'];
                ++$item_count;

                $sql = Sql::factory();
                $sql
                    ->setTable(Core::getTable('yform_history_field'))
                    ->setWhere('history_id = ?', [$history_id])
                    ->delete();

                $sql = Sql::factory();
                $sql
                    ->setTable(Core::getTable('yform_history'))
                    ->setWhere('id = ?', [$history_id])
                    ->delete();
            }

            $this->message = I18n::msg('yform_cronjob_history_delete_message', $item_count);
            return true;
        } catch (Exception $e) {
            $this->message = $e->getMessage();
            return false;
        }
    }

    #[Override]
    public function getTypeName(): string
    {
        return I18n::msg('yform_cronjob_history_delete');
    }

    #[Override]
    public function getParamFields(): array
    {
        $tables = [];
        foreach (Table::getAll() as $table) {
            if ($table->hasHistory()) {
                $tables[$table->getTableName()] = $table->getTableName();
            }
        }

        return [
            [
                'label' => I18n::msg('yform_cronjob_history_delete_tables'),
                'name' => 'table',
                'type' => 'select',
                'options' => $tables,
                'default' => '',
            ],
            [
                'label' => I18n::msg('yform_cronjob_history_delete_interval'),
                'name' => 'interval',
                'type' => 'select',
                'options' => [
                    'monthly' => I18n::msg('yform_cronjob_history_delete_interval_monthly'),
                    'three_months' => I18n::msg('yform_cronjob_history_delete_interval_three_months'),
                    'half_yearly' => I18n::msg('yform_cronjob_history_delete_interval_half_yearly'),
                    'interval_yearly' => I18n::msg('yform_cronjob_history_delete_interval_yearly'),
                ],
                'default' => '',
            ],
        ];
    }
}
