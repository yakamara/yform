<?php

/**
 * Hilfsmethoden für rex_yfom_list.
 *
 * @package redaxo\yform\manager
 */
class rex_yform_list_tools
{
    public static function listFormat($p, $value = '')
    {
        if ('' != $value) {
            $p['value'] = $value;
        }
        switch ($p['list']->getValue('type_id')) {
            case 'validate':
                $styleClass = 'yform-manager-type-validate';
                $p['value'] = str_replace(',', ', ', $p['value']);
                break;
            case 'action':
                $styleClass = 'yform-manager-type-action';
                break;
            default:
                $styleClass = 'yform-manager-type-default';
                break;
        }

        if ('label' == $p['field']) {
            $p['value'] = rex_i18n::translate($p['value']);
        }

        return '<td class="' . $styleClass . '">' . $p['value'] . '</td>';
    }

    public static function editFormat($p)
    {
        return self::listFormat($p, $p['list']->getColumnLink(rex_i18n::msg('yform_function'), '<i class="rex-icon rex-icon-editmode"></i> ' . rex_i18n::msg('yform_edit')));
    }

    public static function deleteFormat($p)
    {
        return self::listFormat($p, $p['list']->getColumnLink(rex_i18n::msg('yform_delete'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('yform_delete')));
    }
}
