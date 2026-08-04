<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Translation\I18n;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('prio')]
class Prio extends AbstractValue
{
    private $preEditScopeWhere;
    private $debug = false;

    public function enterObject()
    {
        $options[1] = I18n::msg('yform_prio_top');

        $scopeWhere = $this->getScopeWhere();
        if (false === $scopeWhere) {
            $options[''] = I18n::msg('yform_prio_bottom');
        } else {
            $this->preEditScopeWhere = $scopeWhere;
            $sql = Sql::factory();
            if ($this->debug) {
                $sql->setDebug();
            }
            $fields = $this->getElement('fields');
            if (!is_array($fields)) {
                $fields = array_filter(explode(',', $fields));
            }
            if (empty($fields)) {
                $fields = ['id'];
            }
            $selectFields = [];
            foreach ($fields as $field) {
                $selectFields[] = $field;
            }
            $sql->setQuery(sprintf(
                'SELECT id, %s, %s as prio FROM %s%s ORDER BY %2$s',
                implode(', ', $selectFields),
                $this->getElement('name'),
                $this->params['main_table'],
                $scopeWhere,
            ));
            $prio = 1;
            while ($sql->hasNext()) {
                if ($sql->getValue('id') != $this->params['main_id']) {
                    $prio = $sql->getValue('prio') + 1;
                    $label = [];
                    foreach ($fields as $field) {
                        $label[] = I18n::translate((string) $sql->getValue($field), false);
                    }
                    $options[$prio] = I18n::msg('yform_prio_after', implode(' | ', $label));
                }
                $sql->next();
            }
        }

        if (!$this->params['send'] && '' == $this->getValue()) {
            if ('' == $this->getElement('default')) {
                $this->setValue($prio ?? '');
            } else {
                $this->setValue($this->getElement('default'));
            }
        }

        if (!is_array($this->getValue())) {
            // Cast: the column is nullable, so a stored NULL arrives here as null.
            $this->setValue(explode(',', (string) $this->getValue()));
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.select-view.tpl.php', 'view'], ['options' => $options, 'multiple' => false, 'size' => 1]);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('select', ['options' => $options, 'multiple' => false, 'size' => 1]);
            }
        }

        $this->setValue(implode(',', $this->getValue()));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDB()) {
            // The column is INT. An empty value means "at the end" and must not reach
            // it verbatim: REDAXO 5 ran with SQL_MODE="" and silently turned '' into 0,
            // REDAXO 6 runs strict and rejects the whole INSERT. postAction() renumbers
            // the entire scope right after the save, so this placeholder never survives.
            $value = $this->getValue();
            $this->params['value_pool']['sql'][$this->getName()] = '' === $value ? ($prio ?? 0) : $value;
        }
    }

    public function getDescription(): string
    {
        return 'prio|name|label|fields|scope|defaultwert';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'prio',
            'values' => [
                'name' => ['type' => 'name',         'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',         'label' => I18n::msg('yform_values_defaults_label')],
                'fields' => ['type' => 'select_names', 'label' => I18n::msg('yform_values_prio_fields')],
                'scope' => ['type' => 'select_names', 'label' => I18n::msg('yform_values_prio_scope')],
                'default' => ['type' => 'choice',       'label' => I18n::msg('yform_values_prio_default'), 'choices' => [1 => 'Am Anfang', '' => 'Am Ende']],
                'attributes' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_attributes'), 'notice' => I18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',        'label' => I18n::msg('yform_values_defaults_notice')],
            ],
            'description' => I18n::msg('yform_values_prio_description'),
            'db_type' => ['int'],
            'multi_edit' => false,
        ];
    }

    public function postAction(): void
    {
        $sql = Sql::factory();
        if ($this->debug) {
            $sql->setDebug();
        }
        $scopeWhere = $this->getScopeWhere();
        if (null !== $this->preEditScopeWhere && $scopeWhere !== $this->preEditScopeWhere) {
            $this->setValue($this->getElement('default'));
        }

        if ('' === $this->getValue()) {
            $order = 'IF(`id` = %d, 1, 0), `%2$s`';
        } else {
            $order = '`%2$s`, IF(`id` = %d, 0, 1)';
        }
        $sql->setQuery('SET @count = 0');
        $sql->setQuery(sprintf(
            'UPDATE `%s` SET `%s` = (SELECT @count := @count + 1)%s ORDER BY ' . $order,
            $this->params['main_table'],
            $this->getElement('name'),
            $scopeWhere,
            $this->params['main_id'],
        ));
    }

    protected function getScopeWhere()
    {
        $sql = Sql::factory();
        $scope = $this->getElement('scope');
        if (!is_array($scope) && $scope) {
            $scope = array_filter(explode(',', $scope));
        }
        if (!$scope) {
            return '';
        }
        $where = [];
        foreach ($scope as $column) {
            if (isset($this->params['value_pool']['sql'][$column])) {
                $value = $this->params['value_pool']['sql'][$column];
            } elseif (isset($this->params['sql_object']) && $this->params['sql_object']->hasValue($column)) {
                $value = $this->params['sql_object']->getValue($column);
            } elseif ($this->params['main_id'] > 0) {
                $sql = Sql::factory();
                if ($this->debug) {
                    $sql->setDebug();
                }
                $sql->setQuery(sprintf(
                    'SELECT `%s` FROM `%s` WHERE id = %d',
                    $column,
                    $this->params['main_table'],
                    $this->params['main_id'],
                ));
                $value = $sql->getValue($column);
            }
            if (!isset($value)) {
                return false;
            }

            $value = $sql->escape($value);
            $where[] = sprintf('`%s` = %s', $column, $value);
        }
        return ' WHERE ' . implode(' AND ', $where);
    }
}
