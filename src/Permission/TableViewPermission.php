<?php

namespace Yakamara\YForm\Permission;

final class TableViewPermission extends TablePermission
{
    public const string KEY = 'yform_table_view';

    protected static function labelKey(): string
    {
        return 'yform_perm_table_view';
    }

    protected static function allLabelKey(): string
    {
        return 'yform_perm_table_view_all';
    }
}
