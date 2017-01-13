<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_radio_sql extends rex_yform_value_abstract
{

    function enterObject()
    {
        $sql = $this->getElement('query');

        $teams = rex_sql::factory();
        $teams->setDebug($this->params['debug']);
        $teams->setQuery($sql);

        $options = array();
        foreach ($teams->getArray() as $t) {
            $v = $t['name'];
            $k = $t['id'];
            $options[$k] = $v;
        }

        if (!array_key_exists($this->getValue(), $options)) {
            $this->setValue('');
            $default = $this->getElement('default');
            if($default && array_key_exists($default, $options)) {
                $this->setValue($default);
            }
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.radio.tpl.php', compact('options'));
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'radio_sql|name|label|select id,name from table order by name|[defaultvalue]|';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'radio_sql',
            'values' => array(
                'name'      => array( 'type' => 'name', 'label' => rex_i18n::msg("yform_values_defaults_name") ),
                'label'     => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_defaults_label")),
                'query'     => array( 'type' => 'text', 'label' => 'Query mit "select id, name from .."'),
                'default'   => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_radio_default")),
                'attributes'=> array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_defaults_attributes"), 'notice' => rex_i18n::msg("yform_values_defaults_attributes_notice")),
                'notice'    => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_defaults_notice")),
                'no_db'     => array( 'type' => 'no_db', 'label' => rex_i18n::msg("yform_values_defaults_table"), 'default' => 0),
            ),
            'description' => 'Hiermit kann man SQL Abfragen als Radioliste nutzen',
            'dbtype' => 'text'
        );
    }

    static function getListValue($params)
    {
        $return = array();

        $query = $params['params']['field']['query'];
        $query_params = [];
        $pos = strrpos(strtoupper($query), 'ORDER BY ');
        if ( $pos !== false) {
            $query = substr($query, 0, $pos);
        }

        $pos = strrpos(strtoupper($query), 'LIMIT ');
        if ( $pos !== false) {
            $query = substr($query, 0, $pos);
        }

        $where = ' `id` = ?';
        $query_params[] = $params['value'];

        $pos = strrpos(strtoupper($query), 'WHERE ');
        if ( $pos !== false) {
            $query = substr($query, 0, $pos) . ' WHERE ' . $where . ' AND ' . substr($query, $pos + strlen('WHERE '));

        } else {
            $query .= ' WHERE ' . $where;

        }

        $db = rex_sql::factory();
        $db_array = $db->getArray($query, $query_params);

        foreach ($db_array as $entry) {
            $return[] = $entry['name'];
        }

        if (count($return) == 0 && $params['value'] != '' && $params['value'] != '0') {
            $return[] = $params['value'];
        }

        return implode('<br />', $return);
    }

    public static function getSearchField($params)
    {
        $options = array();
        $options['(empty)'] = "(empty)";
        $options['!(empty)'] = "!(empty)";

        $options_sql = rex_sql::factory();
        $options_sql->setQuery($params['field']['query']);

        foreach ($options_sql->getArray() as $t) {
            $options[$t['id']] = $t['name'];
        }

        $params['searchForm']->setValueField('select', array(
        'name' => $params['field']->getName(),
        'label' => $params['field']->getLabel(),
        'options' => $options,
        'multiple' => 1,
        'size' => 5,
        )
        );
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();
        $field = $params['field']->getName();
        $values = (array) $params['value'];

        $where = array();
        foreach($values as $value) {
            switch($value){
                case("(empty)"):
                    $where[] = $sql->escapeIdentifier($field).' = ""';
                    break;
                case("!(empty)"):
                    $where[] = $sql->escapeIdentifier($field).' != ""';
                    break;
                default:
                    $where[] = ' ( FIND_IN_SET( ' . $sql->escape($value) . ', ' . $sql->escapeIdentifier($field) . ') )';
                    break;
            }
        }

        if (count($where) > 0) {
            return ' ( ' . implode(" or ", $where) . ' )';

        }

    }

}
