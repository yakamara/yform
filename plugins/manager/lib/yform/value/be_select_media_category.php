<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_be_select_media_category extends rex_yform_value_abstract
{

    function enterObject()
    {
        if (is_array($this->getValue())) {
            $this->setValue(implode(',', $this->getValue()));
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(4) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput()) {
            return;
        }

        $multiple = $this->getElement('multiple') == 1;

        $options = array();
        if ($this->getElement('no_category')) {
            $options[0] = rex_i18n::msg('yform_values_be_select_media_category_no_category');
        }

        $checkPerms = $this->getElement('check_perms');

        $add = function (rex_media_category $cat, $level = 0) use (&$add, &$options, $checkPerms) {

            if (!$checkPerms || rex::getUser()->getComplexPerm('media')->hasCategoryPerm($cat->getId())) {
                $cid = $cat->getId();
                $cname = $cat->getName();

                $cname .= ' [' . $cid . ']';

                $options[$cid] = str_repeat('&nbsp;&nbsp;&nbsp;', $level) . $cname;
                $childs = $cat->getChildren();
                if (is_array($childs)) {
                    foreach ($childs as $child) {
                        $add($child, $level + 1);
                    }
                }
            }
        };
        if ($rootId = $this->getElement('category')) {
            if ($rootCat = rex_media_category::get($rootId)) {
                $add($rootCat);
            }
        } else {
            if ($rootCats = rex_media_category::getRootCategories()) {
                foreach ($rootCats as $rootCat) {
                    $add($rootCat);
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
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'be_select_media_category',
            'values' => array(
                'name'     => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'check_perms'     => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_be_select_media_category_check_perms"), 'default' => 1),
                'no_category'        => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_be_select_media_category_homepage"), 'default' => 1),
                'category' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_be_select_media_category_category"), 'value' => 0),
                'multiple' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_be_select_media_category_multiple")),
                'size'     => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_be_select_media_category_size")),
                'no_db'    => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),          'default' => 0),
                'attributes'   => array( 'type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')),
                'notice'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_notice")),
            ),
            'description' => rex_i18n::msg("yform_values_be_select_media_category_description"),
            'formbuilder' => false,
            'dbtype' => 'text'
        );

    }

    static function getListValue($params)
    {
        $return = array();

        foreach (explode(',', $params['value']) as $id) {
            if ($cat = rex_media_category::get($id)) {
                $return[] = $cat->getName();
            }
        }

        return implode('<br />', $return);
    }

}
