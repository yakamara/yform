<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_be_select_category extends rex_yform_value_abstract
{

    function enterObject()
    {

        $multiple = $this->getElement('multiple') == 1;

        $options = array();
        if ($this->getElement('homepage')) {
            $options[0] = 'Homepage';
        }

        $ignoreOfflines = $this->getElement('ignore_offlines');
        $checkPerms = $this->getElement('check_perms');
        $clang = (int) $this->getElement('clang');

        $add = function (OOCategory $cat, $level = 0) use (&$add, &$options, $ignoreOfflines, $checkPerms, $clang) {

            if (!$checkPerms || rex::getUser()->hasCategoryPerm($cat->getId(), false)) {
                $cid = $cat->getId();
                $cname = $cat->getName();

                if (rex::getUser()->hasPerm('advancedMode[]')) {
                    $cname .= ' [' . $cid . ']';
                }

                $options[$cid] = str_repeat('&nbsp;&nbsp;&nbsp;', $level) . $cname;
                $childs = $cat->getChildren($ignoreOfflines, $clang);
                if (is_array($childs)) {
                    foreach ($childs as $child) {
                        $add($child, $level + 1);
                    }
                }
            }
        };
        if ($rootId = $this->getElement('category')) {
            if ($rootCat = OOCategory::getCategoryById($rootId, $clang)) {
                $add($rootCat);
            }
        } else {
            if (!$checkPerms || rex::getUser()->isAdmin() || rex::getUser()->hasPerm('csw[0]')) {
                if ($rootCats = OOCategory::getRootCategories($ignoreOfflines, $clang)) {
                    foreach ($rootCats as $rootCat) {
                        $add($rootCat);
                    }
                }
            } elseif (rex::getUser()->hasMountpoints()) {
                $mountpoints = rex::getUser()->getMountpoints();
                foreach ($mountpoints as $id) {
                    $cat = OOCategory::getCategoryById($id, $clang);
                    if ($cat && !rex::getUser()->hasCategoryPerm($cat->getParentId())) {
                        $add($cat);
                    }
                }
            }
        }

        if ($multiple) {
            $size = (int) $this->getElement('size');
            if ($size < 2) {
                $size = count($options);
            }
        } else {
            $size = 1;
        }

        if (!is_array($this->getValue())) {
            $this->setValue(explode(',', $this->getValue()));
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.select.tpl.php', compact('options', 'multiple', 'size'));

        $this->setValue(implode(',', $this->getValue()));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(4) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return '';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'be_select_category',
            'values' => array(
                'name'     => array( 'type' => 'name',   'label' => 'Feld' ),
                'label'    => array( 'type' => 'text',    'label' => 'Bezeichnung'),
                'ignore_offlines' => array( 'type' => 'boolean', 'label' => 'Ignoriere Offline-Kategorien', 'default' => 1),
                'check_perms'     => array( 'type' => 'boolean', 'label' => 'Prüfe Rechte', 'default' => 1),
                'homepage'        => array( 'type' => 'boolean', 'label' => 'Füge "Homepage"-Eintrag (Root) hinzu', 'default' => 1),
                'category' => array( 'type' => 'text',    'label' => 'Root-ID', 'value' => 0),
                'clang'    => array( 'type' => 'text',    'label' => 'Sprache', 'value' => 0),
                'multiple' => array( 'type' => 'boolean', 'label' => 'Mehrere Felder möglich'),
                'size'     => array( 'type' => 'text',    'label' => 'Höhe der Auswahlbox'),
                'no_db'    => array( 'type' => 'no_db',   'label' => 'Datenbank',          'default' => 0),
            ),
            'description' => 'Ein Selectfeld für die Strukturkategorien',
            'dbtype' => 'text'
        );

    }

    static function getListValue($params)
    {
        $return = array();

        foreach (explode(',', $params['value']) as $id) {
            if ($cat = OOCategory::getCategoryById($id, (int) $params['params']['field']['clang'])) {
                $return[] = $cat->getName();
            }
        }

        return implode('<br />', $return);
    }

}
