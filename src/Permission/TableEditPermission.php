<?php

namespace Yakamara\YForm\Permission;

final class TableEditPermission extends TablePermission
{
    public const string KEY = 'yform_table_edit';

    protected static function labelKey(): string
    {
        return 'yform_perm_table_edit';
    }

    protected static function allLabelKey(): string
    {
        return 'yform_perm_table_edit_all';
    }
}
