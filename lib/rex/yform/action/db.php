<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_db extends rex_yform_action_abstract
{

    function executeAction()
    {

        $sql = rex_sql::factory();
        if ($this->params['debug']) {
            $sql->debugsql = true;
        }

        $main_table = '';
        if (!$main_table = $this->getElement(2)) {
            $main_table = $this->params['main_table'];
        }

        if ($main_table == '') {
                $this->params['form_show'] = true;
                $this->params['hasWarnings'] = true;
                $this->params['warning_messages'][] = $this->params['Error-Code-InsertQueryError'];
                return false;
        }

        $sql->setTable($main_table);

        $where = '';
        if ($where = $this->getElement(3)) {
            if ($where == 'main_where') {
                $where = $this->params['main_where'];
            }
        }

        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            $sql->setValue($key, $value);
            if ($where != '') {
                $where = str_replace('###' . $key . '###', addslashes($value), $where);
            }
        }

        try {
            if ($where != '') {
                $sql->setWhere($where);
                $saved = $sql->update();
                $flag = 'update';
            } else {
                $saved = $sql->insert();
                $flag = 'insert';
                $id = $sql->getLastId();
                $this->params['main_id'] = $id;
                $this->params['value_pool']['email']['ID'] = $id;
                // $this->params["value_pool"]["sql"]["ID"] = $id;
            }

        } catch (Exception $sql) {
            $this->params['form_show'] = true;
            $this->params['hasWarnings'] = true;
            $this->params['warning_messages'][] = $this->params['Error-Code-InsertQueryError'];
            echo $sql->getMessage();

        }

        rex_extension::registerPoint(new rex_extension_point('REX_YFORM_SAVED'), $sql, array('form' => $this, 'sql' => $sql, 'yform' => true));

    }

    function getDescription()
    {
        return 'action|db|tblname|[where(id=2)/main_where]';
    }

}
