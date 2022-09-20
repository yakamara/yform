<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$_csrf_key = 'yform_rest_token_access';

echo rex_view::title(rex_i18n::msg('yform_rest_token_access_header'));

$table = rex::getTablePrefix() . 'yform_rest_token_access';
$bezeichner = rex_i18n::msg('yform_rest_token_access');

$func = rex_request('func', 'string', '');
$page = rex_request('page', 'string', '');
$data_id = rex_request('data_id', 'int');
$content = '';
$show_list = true;

if ('delete' == $func && !rex_csrf_token::factory($_csrf_key)->isValid()) {
    echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
} elseif ('delete' == $func) {
    $query = "delete from $table where id='" . $data_id . "' ";
    $delsql = rex_sql::factory();
    $delsql->setQuery($query);
    $content = rex_view::success(rex_i18n::msg('yform_rest_token_access_deleted'));
} elseif ('edit' == $func || 'add' == $func) {
    $form_data = [];

    $form_data[] = 'choice|token_id|translate:yform_rest_token|select id, name from '.rex::getTablePrefix() . 'yform_rest_token'.'|';
    $form_data[] = 'datetime|datetime_created|translate:yform_rest_token_access_datetime_created';
    $form_data[] = 'text|url|translate:yform_rest_token_url';

    $yform = rex_yform::factory();
    $yform->setObjectparams('form_action', 'index.php?page=yform/rest/access');

    $yform->setFormData(implode("\n", $form_data));
    $yform->setObjectparams('form_showformafterupdate', 1);

    $yform_clone = clone $yform;

    if ('edit' == $func) {
        $title = rex_i18n::msg('yform_rest_token_access_update');
        $yform->setValueField('submit', ['name' => 'submit', 'labels' => rex_i18n::msg('yform_save').','.rex_i18n::msg('yform_save_apply'), 'values' => '1,2', 'no_db' => true, 'css_classes' => 'btn-save,btn-apply']);
        $yform->setHiddenField('data_id', $data_id);
        $yform->setHiddenField('func', $func);
        $yform->setActionField('db', [$table, "id=$data_id"]);
        $yform->setActionField('showtext', [rex_view::success(rex_i18n::msg('yform_rest_token_updated')), '', '', 1]);
        $yform->setObjectparams('main_id', $data_id);
        $yform->setObjectparams('main_where', "id=$data_id");
        $yform->setObjectparams('main_table', $table);
        $yform->setObjectparams('getdata', true);
    } else {
        $yform->setHiddenField('func', $func);
        $title = rex_i18n::msg('yform_rest_token_create');
        $yform->setValueField('submit', ['name' => 'submit', 'labels' => rex_i18n::msg('yform_add').','.rex_i18n::msg('yform_add_apply'), 'values' => '1,2', 'no_db' => true, 'css_classes' => 'btn-save,btn-apply']);
        $yform->setActionField('db', [$table]);
        $yform->setActionField('showtext', [rex_view::success(rex_i18n::msg('yform_rest_token_info_added')), '', '', 1]);
    }

    $yform->executeFields();

    $submit_type = 1; // normal, 2=apply
    foreach ($yform->objparams['values'] as $f) {
        if ('submit' == $f->getName()) {
            if (2 == $f->getValue()) { // apply
                $submit_type = 2;
            }
        }
    }

    $content = $yform->executeActions();

    if ($yform->objparams['actions_executed']) {
        switch ($func) {
            case 'edit':
                if (2 == $submit_type) {
                    $fragment = new rex_fragment();
                    $fragment->setVar('class', 'edit', false);
                    $fragment->setVar('title', $title);
                    $fragment->setVar('body', $content, false);
                    $content = $fragment->parse('core/page/section.php');

                    $show_list = false;
                } else {
                    $content = rex_view::success(rex_i18n::msg('yform_rest_token_access_updated'));
                }
                break;
            case 'add':
            default:
                if (2 == $submit_type) {
                    $title = rex_i18n::msg('yform_email_update');
                    $data_id = $yform->objparams['main_id'];
                    $func = 'edit';

                    $yform = $yform_clone;
                    $yform->setHiddenField('func', $func);
                    $yform->setHiddenField('data_id', $data_id);
                    $yform->setActionField('db', [$table, "id=$data_id"]);
                    $yform->setObjectparams('main_id', $data_id);
                    $yform->setObjectparams('main_where', "id=$data_id");
                    $yform->setObjectparams('main_table', $table);
                    $yform->setObjectparams('getdata', true);
                    $yform->setValueField('submit', ['name' => 'submit', 'labels' => rex_i18n::msg('yform_save').','.rex_i18n::msg('yform_save_apply'), 'values' => '1,2', 'no_db' => true, 'css_classes' => 'btn-save,btn-apply']);
                    $yform->executeFields();

                    $content = $yform->executeActions();
                    $fragment = new rex_fragment();
                    $fragment->setVar('class', 'edit', false);
                    $fragment->setVar('title', $title);
                    $fragment->setVar('body', $content, false);
                    $content = rex_view::success(rex_i18n::msg('yform_rest_token_access_added')).$fragment->parse('core/page/section.php');

                    $show_list = false;
                } else {
                    $content = rex_view::success(rex_i18n::msg('yform_rest_token_access_added'));
                }
        }
    } else {
        $fragment = new rex_fragment();
        $fragment->setVar('class', 'edit', false);
        $fragment->setVar('title', $title);
        $fragment->setVar('body', $content, false);
        $content = $fragment->parse('core/page/section.php');

        $show_list = false;
    }
}

echo $content;

if ($show_list) {
    $add_sql = ' ORDER BY id desc';
    $link = '';

    $sql = "select * from $table " . $add_sql;

    $list = rex_list::factory($sql);
    $list->addTableAttribute('summary', rex_i18n::msg('yform_rest_token_header_summary'));
    $list->addTableAttribute('class', 'table-striped table-hover');
    $list->addTableColumnGroup([40, 40, '*', 153, 153]);

    $tdIcon = '<i class="rex-icon rex-icon-template"></i>';
    $thIcon = '<!--<a class="rex-link-expanded" href="' . $list->getUrl(['func' => 'add']) . '"' . rex::getAccesskey(rex_i18n::msg('create_token'), 'add') . ' title="' . rex_i18n::msg('create_template') . '"><i class="rex-icon rex-icon-add-template"></i></a>-->';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);

    $list->setColumnLabel('id', 'ID');
    $list->setColumnLayout('id', ['<th class="rex-small">###VALUE###</th>', '<td class="rex-small">###VALUE###</td>']);
    $list->setColumnParams('id', ['page' => $page, 'func' => 'edit', 'data_id' => '###id###']);

    $list->setColumnLabel('token_id', rex_i18n::msg('yform_rest_token_name'));
    $list->setColumnLayout('token_id', ['<th class="rex-small">###VALUE###</th>', '<td class="rex-small">###VALUE###</td>']);
    $list->setColumnParams('token_id', ['page' => 'yform/rest/token', 'func' => 'edit', 'data_id' => '###rest_id###']);

    $list->setColumnFormat('token_id', 'custom', static function ($params) {
        $token = rex_yform_rest_auth_token::get($params['subject']);
        if ($token) {
            return '<a href="index.php?page=yform/rest/token&func=edit&data_id='.$params['subject'].'">'.$token['name'].'</a>';
        }
        return '-'; // rex_i18n::translate($params['value']).' [###table_name###]<p><a href="index.php?page=yform/manager/data_edit&table_name=###table_name###"><i class="rex-icon rex-icon-edit"></i> '.rex_i18n::msg('yform_edit_datatable').'</a></p>';
    });

    $list->addColumn(rex_i18n::msg('yform_delete'), rex_i18n::msg('yform_delete'));
    $list->setColumnParams(rex_i18n::msg('yform_delete'), ['page' => $page, 'func' => 'delete', 'data_id' => '###id###'] + rex_csrf_token::factory($_csrf_key)->getUrlParams());
    $list->addLinkAttribute(rex_i18n::msg('yform_delete'), 'onclick', 'return confirm(\' id=###id### ' . rex_i18n::msg('yform_delete') . ' ?\')');

    $list->removeColumn('header');

    $list->setNoRowsMessage(rex_i18n::msg('yform_rest_token_not_found'));

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('yform_rest_token_access_caption'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}
