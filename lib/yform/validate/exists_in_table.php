<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_exists_in_table extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        if ($this->params['send'] == '1') {
            $db = rex_sql::factory();
            $db->setDebug($this->params['debug']);

            $table = $this->getElement(3);
            $labels = $this->getElement(2);
            $fields = $this->getElement(4);

            $labels = explode(',', $labels);
            $fields = explode(',', $fields);

            $qfields = [];
            foreach ($this->getObjects() as $k => $o) {
                if ($this->isObject($o)) {
                    if (in_array($o->getName(), $labels)) {
                        $label_key = array_search($o->getName(), $labels);
                        $name = $fields[$label_key]; // $o->getName()
                        $value = $o->getValue();

                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $qfields[$o->getId()] = $db->escapeIdentifier($name) . ' = ' . $db->escape($value);
                    }
                }
            }

            if (count($qfields) != count($fields)) {
                $this->params['warning'][] = $this->params['error_class'];
                $this->params['warning_messages'][] = $this->getElement(5);
                return;
            }

            $sql = 'select * from ' . $table . ' WHERE (' . implode(' AND ', $qfields) . ')';
            $extras = trim($this->getElement(6));
            if ($extras != '') {
                $sql .= ' and ('.$extras.')';
            }
            $sql .= ' LIMIT 2';

            $db->setQuery($sql);
            if ($db->getRows() == 0) {
                foreach ($qfields as $qfield_id => $qfield_name) {
                    $this->params['warning'][$qfield_id] = $this->params['error_class'];
                }
                $this->params['warning_messages'][] = $this->getElement(5);
            }
        }
    }

    public function getDescription()
    {
        return 'validate|exists_in_table|name,name2|tablename|fieldname,fieldname2|warning_message|[extras z.B. status=1';
    }
}
