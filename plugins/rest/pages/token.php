<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$_csrf_key = 'yform_rest_token';

echo rex_view::title(rex_i18n::msg('yform_rest_token_header'));

$table = rex::getTablePrefix() . 'yform_rest_token';
$bezeichner = rex_i18n::msg('yform_rest_token');

$func = rex_request('func', 'string', '');
$page = rex_request('page', 'string', '');
$data_id = rex_request('data_id', 'int');
$content = '';
$show_list = true;

$routes = [];
foreach(rex_yform_rest::getRoutes() as $route) {
    $routes[] = $route->getPath();
}

if ($func == 'delete' && !rex_csrf_token::factory($_csrf_key)->isValid()) {
    echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
} elseif ($func == 'delete') {
    $query = "delete from $table where id='" . $data_id . "' ";
    $delsql = rex_sql::factory();
    $delsql->setQuery($query);
    $content = rex_view::success(rex_i18n::msg('yform_rest_token_deleted'));
} elseif ($func == 'edit' || $func == 'add') {
    $form_data = [];

    $dummyToken = bin2hex(random_bytes((32-(32%2))/2));

    $form_data[] = 'checkbox|status|translate:yform_rest_token_status';
    $form_data[] = 'text|name|translate:yform_rest_token_name';
    $form_data[] = 'validate|empty|name|translate:yform_rest_token_name_validate';
    $form_data[] = 'text|token|translate:yform_rest_token_token|#notice:'.rex_i18n::msg('yform_rest_token_token_notice', $dummyToken);;
    $form_data[] = 'validate|empty|token|translate:yform_rest_token_token_validate';
    $form_data[] = 'choice|interval|translate:yform_rest_token_interval|translate:yform_rest_token_none=none,translate:yform_rest_token_overall=overall,translate:yform_rest_token_per_hour=hour,translate:yform_rest_token_per_day=day,translate:yform_rest_token_per_month=month|#attributes:{"class": "form-control yform-rest-token-interval-select"}';
    $form_data[] = 'integer|amount|translate:yform_rest_token_amount';
    $form_data[] = 'choice|paths|translate:yform_rest_token_token_paths|'.implode(',', $routes).'||1';

    $yform = rex_yform::factory();
    $yform->setObjectparams('form_action', 'index.php?page=yform/rest/token');
    $yform->setObjectparams('form_name', 'yform-rest-token-form');

    $yform->setFormData(implode("\n", $form_data));
    $yform->setObjectparams('form_showformafterupdate', 1);

    $yform_clone = clone $yform;

    if ($func == 'edit') {
        $title = rex_i18n::msg('yform_rest_token_update');
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
        if ($f->getName() == 'submit') {
            if ($f->getValue() == 2) { // apply
                $submit_type = 2;
            }
        }
    }

    $content = $yform->executeActions();

    if ($yform->objparams['actions_executed']) {
        if ($func == 'edit') {
            if ($submit_type == 2) {
                $fragment = new rex_fragment();
                $fragment->setVar('class', 'edit', false);
                $fragment->setVar('title', $title);
                $fragment->setVar('body', $content, false);
                $content = $fragment->parse('core/page/section.php');

                $show_list = false;
            } else {
                $content = rex_view::success(rex_i18n::msg('yform_rest_token_updated'));
            }
        } elseif ($func == 'add') {
            if ($submit_type == 2) {
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
                $content = rex_view::success(rex_i18n::msg('yform_rest_token_added')).$fragment->parse('core/page/section.php');

                $show_list = false;
            } else {
                $content = rex_view::success(rex_i18n::msg('yform_rest_token_added'));
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

?><script>
$(document).ready(function() {
    $(".yform-rest-token-interval-select").on("change", function(e) {

        if ($(this).val() == "none") {
            $("#yform-yform-rest-token-form-amount").css("display","none");
        } else {
            $("#yform-yform-rest-token-form-amount").css("display","block");
        }
    }).trigger("change");
});
</script><?php

if ($show_list) {
    $add_sql = ' ORDER BY name';
    $link = '';

    $sql = "select * from $table " . $add_sql;

    $list = rex_list::factory($sql);
    $list->addTableAttribute('summary', rex_i18n::msg('yform_rest_token_header_summary'));
    $list->addTableAttribute('class', 'table-striped');
    $list->addTableColumnGroup([40, 40, '*', 153, 153]);

    $tdIcon = '<i class="rex-icon rex-icon-template"></i>';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '"' . rex::getAccesskey(rex_i18n::msg('create_token'), 'add') . ' title="' . rex_i18n::msg('create_template') . '"><i class="rex-icon rex-icon-add-template"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);

    $list->setColumnLabel('id', 'ID');
    $list->setColumnLayout('id', ['<th class="rex-small">###VALUE###</th>', '<td class="rex-small">###VALUE###</td>']);

    $list->setColumnLabel('name', rex_i18n::msg('yform_rest_token_name'));
    $list->setColumnParams('name', ['page' => $page, 'func' => 'edit', 'data_id' => '###id###']);

    $list->setColumnLabel('token', rex_i18n::msg('yform_rest_token_token'));

    $list->setColumnFormat('amount', 'custom', function ($params) {
        $subject = $params['subject'];
        /* @var $list rex_list */
        $list = $params['list'];
        $maxHits = $list->getValue('amount');

        $return = $maxHits;

        if ($list->getValue('interval') != 'none') {
            $currentHits = \rex_yform_rest_auth_token::getCurrentIntervalAmount($list->getValue('interval'), $list->getValue('id'));
            $return = $currentHits . ' / ' . $maxHits . ' / ' . $list->getValue('interval') . '';
        }

        return $return;
    });

    $list->removeColumn('interval');

    $list->addColumn(rex_i18n::msg('yform_delete'), rex_i18n::msg('yform_delete'));
    $list->setColumnParams(rex_i18n::msg('yform_delete'), ['page' => $page, 'func' => 'delete', 'data_id' => '###id###'] + rex_csrf_token::factory($_csrf_key)->getUrlParams());
    $list->addLinkAttribute(rex_i18n::msg('yform_delete'), 'onclick', 'return confirm(\' id=###id### ' . rex_i18n::msg('yform_delete') . ' ?\')');

    $list->setNoRowsMessage(rex_i18n::msg('yform_rest_token_not_found'));

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('yform_rest_token_caption'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}
