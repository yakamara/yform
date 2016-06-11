<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_be_table extends rex_yform_value_abstract
{

    function preValidateAction()
    {
        $columns = (int) $this->getElement(3);
        if ($columns < 1) {
            $columns = 1;
        }
        $id = $this->getId();
        $values = array();
        if ($this->params['send']) {

            $i = 0;

            $search = array(',', ';');
            $replace = array('‚', '⁏'); // , -> alt-s

            if (isset($_REQUEST['v'][$id])) {
                foreach ($_REQUEST['v'][$id] as $c) {
                    for ($r = 0; $r < count($c); $r++) {
                        if (!isset($values[$r])) {
                            $values[$r] = '';
                        }
                        if ($i > 0) {
                            $values[$r] .= ',';
                        }
                        if (isset($c[$r])) {
                            $values[$r] .= str_replace($search, $replace, trim($c[$r]));
                        }
                    }
                    $i++;
                    // die nur den Trenner haben loeschen
                    if (count($values) > 0) {
                        foreach ($values as $key => $val) {
                            if (trim($val) == ',') {
                                unset($values[$key]);
                            }
                        }
                    }
                }
            }

            $this->setValue('');
            $i = 0;
            foreach ($values as $value) {
                if ($i > 0) {
                    $this->setValue($this->getValue() . ';');
                }
                $v = explode(',', $value);
                $e = '';
                $j = 0;
                for ($r = 0; $r < $columns; $r++) {
                    if ($j > 0) {
                        $e .= ',';
                    }
                    $e .= @$v[$r];
                    $j++;
                }
                $this->setValue($this->getValue() . $e);
                $i++;
            }

        }

    }

    function enterObject()
    {
        $columnsCount = max(1, (int) $this->getElement(3));
        $columns = explode(',', $this->getElement(4));
        if (count($columns) < $columnsCount) {
            $columns = array_pad($columns, $columnsCount, '');
        } elseif (count($columns) > $columnsCount) {
            $columns = array_slice($columns, 0, $columnsCount);
        }

        // "1,1000,121;10,900,1212;100,800,1212;"

        $data = array();
        $rows = explode(';', $this->getValue());
        foreach ($rows as $rawRow) {
            $row = array();
            $rawColumns = explode(',', $rawRow);
            for ($i = 0; $i < $columnsCount; ++$i) {
                $row[$i] = isset($rawColumns[$i]) ? $rawColumns[$i] : '';
            }
            $data[] = $row;
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.be_table.tpl.php', compact('columns', 'data'));

        $this->params['value_pool']['email'][$this->getName()] = stripslashes($this->getValue());
        if ($this->getElement(5) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
        return;

    }

    function getDescription()
    {
        return 'be_table -> Beispiel: be_table|name|label|Anzahl Spalten|Menge,Preis/Stück';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'be_table',
            'values' => array(
                'name'    => array( 'type' => 'name',   'label' => 'Name' ),
                'label'   => array( 'type' => 'text',    'label' => 'Bezeichnung'),
                'size'    => array( 'type' => 'text',    'label' => 'Anzahl Spalten'),
                'columns' => array( 'type' => 'text',    'label' => 'Bezeichnung der Spalten (Menge,Preis,Irgendwas)'),
            ),
            'description' => rex_i18n::msg("yform_values_be_table_description"),
            'dbtype' => 'text'
        );
    }


}
