<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_select_sql extends rex_yform_value_abstract
{

    static $getListValues = array();

    function enterObject()
    {
        $multiple = $this->getElement('multiple') == 1;

        // ----- query
        $sql = $this->getElement('query');

        $options_sql = rex_sql::factory();
        $options_sql->setDebug($this->params['debug']);
        $options_sql->setQuery($sql);

        $options = array();
        $option_names = array();
        foreach ($options_sql->getArray() as $t) {
            $v = $t['name'];
            $k = $t['id'];
            $options[$k] = $v;
            $option_names[$k] = $t['name'];
        }

        // ----- default value
        if ($this->getValue() == '' && $this->getElement('default') != '') {
            $this->setValue($this->getElement('default'));
        }

        if ($multiple) {
            $size = (int) $this->getElement('size');
            if ($size < 2) {
                $size = count($options);
            }
        } else {
            $size = 1;

            // mit --- keine auswahl ---
            if ($this->getElement('empty_option') == 1) {
                $options = array('0' => $this->getElement('empty_value')) + $options;
            }
        }

        if (!is_array($this->getValue())) {
            $this->setValue(explode(',', $this->getValue()));
        }

        // ---------- rex_yform_set
        if (isset($this->params['rex_yform_set'][$this->getName()]) && !is_array($this->params['rex_yform_set'][$this->getName()])) {
            $value = $this->params['rex_yform_set'][$this->getName()];
            $values = array();
            if (array_key_exists($value, $options)) {
                $values[] = $value;
            }
            $this->setValue($values);
            $this->setElement('disabled', true);
        }
        // ----------

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.select.tpl.php', compact('options', 'multiple', 'size'));
        }

        $this->setValue(implode(',', $this->getValue()));

        $this->params['value_pool']['email'][$this->getElement(1)] = $this->getValue();
        if ($this->getElement(5) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getElement(1)] = $this->getValue();

        }

    }


    function getDescription()
    {
        return 'select_sql|label|Bezeichnung:| select id,name from table order by name | [defaultvalue] | [no_db] |1/0 Leeroption|Leeroptionstext|1/0 Multiple Feld|selectsize';
    }


    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'select_sql',
            'values' => array(
                'name'         => array( 'type' => 'name',    'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'        => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'query'        => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_select_sql_query")),
                'default'      => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_select_sql_default")),
                'no_db'        => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
                'empty_option' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_select_sql_empty_option")),
                'empty_value'  => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_select_sql_empty_value")),
                'multiple'     => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_select_sql_multiple")),
                'size'         => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_select_sql_size")),
                'attributes'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_attributes"), 'notice' => rex_i18n::msg("yform_values_defaults_attributes_notice")),
                'notice'       => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_notice")),
            ),
            'description' => rex_i18n::msg("yform_values_select_sql_description"),
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

        $multiple = (isset($params['params']['field']['multiple'])) ? (int) $params['params']['field']['multiple'] : 0;
        if ($multiple != 1) {
            $where = ' `id` = ?';
            $query_params[] = $params['value'];

        } else {
            $where = ' FIND_IN_SET(`id`, ?)';
            $query_params[] = $params['value'];

        }

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
