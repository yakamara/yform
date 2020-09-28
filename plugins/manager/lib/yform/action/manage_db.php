<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_manage_db extends rex_yform_action_abstract
{
    public function executeAction()
    {
        // START - Spezialfall "be_em_relation"
        /*
        $be_em_table_field = "";
        if($this->params["value_pool"]["sql"]["type_name"] == "be_em_relation")
        {
            $be_em_table_field = $this->params["value_pool"]["sql"]["f1"];
            $this->params["value_pool"]["sql"]["f1"] = $this->params["value_pool"]["sql"]["f3"]."_".$this->params["value_pool"]["sql"]["f1"];
        }
        */
        // ENDE - Spezialfall

        // ********************************* TABLE A

        // $this->params["debug"]= TRUE;
        $sql = rex_sql::factory();
        $sql->setDebug($this->params['debug']);

        $main_table = '';
        if ('' != $this->getElement(2)) {
            $main_table = $this->getElement(2);
        } else {
            $main_table = $this->params['main_table'];
        }

        if ('' == $main_table) {
            $this->params['form_show'] = true;
            $this->params['hasWarnings'] = true;
            $this->params['warning_messages'][] = $this->params['Error-Code-InsertQueryError'];
            return false;
        }

        $columns = [];
        foreach (rex_sql::showColumns($main_table) as $column) {
            $columns[$column['name']] = true;
        }
        $alterTable = [];
        foreach ($this->params['value_pool']['sql'] as $field => $value) {
            if ('' != $value && !isset($columns[$field])) {
                $alterTable[] = 'ADD `' . $field . '` TEXT NOT NULL';
                $columns[$field] = true;
            }
        }
        if ($alterTable) {
            $sql->setQuery('ALTER TABLE `' . $main_table . '` ' . implode(',', $alterTable));
        }

        $sql->setTable($main_table);

        $where = '';
        if ('' != trim($this->getElement(3))) {
            $where = trim($this->getElement(3));
        }

        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if (isset($columns[$key])) {
                $sql->setValue($key, $value);
            }
            if ('' != $where) {
                $where = str_replace('###' . $key . '###', addslashes($value), $where);
            }
        }

        if ('' != $where) {
            $sql->setWhere($where);
            $sql->update();
            $flag = 'update';
        } else {
            $sql->insert();
            $flag = 'insert';
            $id = $sql->getLastId();

            $this->params['value_pool']['email']['ID'] = $id;
            $this->params['main_id'] = $id;
            // $this->params["value_pool"]["sql"]["ID"] = $id;
            if (0 == $id) {
                $this->params['form_show'] = true;
                $this->params['hasWarnings'] = true;
                $this->params['warning_messages'][] = $this->params['Error-Code-InsertQueryError'];
            }
        }
    }
}
