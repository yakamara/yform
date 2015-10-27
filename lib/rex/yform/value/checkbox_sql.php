<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_checkbox_sql extends rex_yform_value_abstract
{

    static $getListValues = array();

    function enterObject()
    {

        if (!is_array($this->getValue())) {
            $this->setValue(explode(',', stripslashes($this->getValue())));
        }

        $values = $this->getValue();

        // ----- query
        $sql = $this->getElement(3);

        $options_sql = rex_sql::factory();
        $options_sql->debugsql = $this->params['debug'];
        $options = array();
        foreach ($options_sql->getArray($sql) as $option) {
            $options[$option['id']] = $option['name'];
        }


        $proofed_values = array();
        $proofed_name_values = array();
        foreach ($values as $value) {
            if (array_key_exists($value, $options)) {
                 $proofed_values[$value] = $value;
                 $proofed_name_values[$value] = $options[$value];
            }
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.checkbox_sql.tpl.php', compact('options'));

        $this->setValue(implode(',', $proofed_values));

        $this->params['value_pool']['email'][$this->getName()] = implode(', ', $proofed_name_values);
        if ($this->getElement('no_db') != 1) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        return;

    }


    function getDescription()
    {
        return 'checkbox_sql -> Beispiel: checkbox_sql|label|Bezeichnung:|select id,name from table order by name|';
    }


    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'checkbox_sql',
            'values' => array(
                'name'  => array( 'type' => 'name',    'label' => 'Name' ),
                'label' => array( 'type' => 'text',    'label' => 'Bezeichnung'),
                'query' => array( 'type' => 'text',    'label' => 'Query mit "select id, name from .."')
            ),
            'description' => 'Hiermit kann man SQL Abfragen als Checkboxliste nutzen',
            'dbtype' => 'text'
        );
    }


    static function getListValue($params)
    {
        $return = array();

        $query = $params['params']['field']['f3'];
        $pos = strrpos(strtoupper($query), 'ORDER BY ');
        if ( $pos !== false) {
            $query = substr($query, 0, $pos);
        }

        $pos = strrpos(strtoupper($query), 'LIMIT ');
        if ( $pos !== false) {
            $query = substr($query, 0, $pos);
        }

        $multiple = (int) $params['params']['field']['f8'];
        if ($multiple != 1) {
            $where = ' `id`="' . mysql_real_escape_string($params['value']) . '"';


        } else {
            $where = ' FIND_IN_SET(`id`,"' . mysql_real_escape_string($params['value']) . '")';

        }

        $pos = strrpos(strtoupper($query), 'WHERE ');
        if ( $pos !== false) {
            $query = substr($query, 0, $pos) . ' WHERE ' . $where . ' AND ' . substr($query, $pos + strlen('WHERE '));

        } else {
            $query .= ' WHERE ' . $where;

        }

        $db = rex_sql::factory();
        // $db->debugsql = 1;
        $db_array = $db->getArray($query);

        foreach ($db_array as $entry) {
            $return[] = $entry['name'];
        }


        if (count($return) == 0 && $params['value'] != '' && $params['value'] != '0') {
            $return[] = $params['value'];
        }

        return implode('<br />', $return);
    }

}
