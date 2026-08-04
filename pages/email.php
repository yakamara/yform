<?php

use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\DataList;
use Redaxo\Core\View\Fragment;
use Redaxo\Core\View\Message;
use Redaxo\Core\View\View;
use Yakamara\YForm\Email\Template;
use Yakamara\YForm\YForm;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$_csrf_key = 'yform_email';

echo View::title(I18n::msg('yform_email_templates'));

$table = Core::getTablePrefix() . 'yform_email_template';
$bezeichner = I18n::msg('yform_email_template');
$csuchfelder = ['name', 'mail_from', 'mail_subject', 'body'];

$func = Request::request('func', 'string');
$page = Request::request('page', 'string');
$template_id = Request::request('template_id', 'int', null);
$template_key = Request::request('template_key', 'string', null);
$template = null;

if ($template_key) {
    $template = Template::getTemplate($template_key);
} elseif ($template_id) {
    $template = Template::getTemplateById($template_id);
}

$template_id = null;
if ($template) {
    $template_id = $template['id'];
}

$content = '';
$show_list = true;

if ('delete' == $func && !CsrfToken::factory($_csrf_key)->isValid()) {
    echo Message::error(I18n::msg('csrf_token_invalid'));
} elseif ('delete' == $func && $template_id) {
    Sql::factory()->setQuery('delete from ' . $table . ' where id=:template_id', ['template_id' => $template_id]);
    $content = Message::success(I18n::msg('yform_email_info_template_deleted'));
} elseif (('edit' == $func && $template_id) || 'add' == $func) {
    echo Message::info(I18n::rawMsg('yform_email_info_text'));
    $form_data = [];

    $form_data[] = 'text|name|translate:yform_email_key';
    $form_data[] = 'validate|empty|name|Bitte key eintragen';
    $form_data[] = 'validate|unique|name|Dieser key existiet bereits|' . $table;
    $form_data[] = 'validate|preg_match|name|([a-z0-9_.]+)|Bitte nur Buchstaben (Kleinschreibung), Zahlen und "_" für den key verwenden|' . $table;

    $form_data[] = 'html|html1|<div class="row"><div class="col-md-6">';
    $form_data[] = 'text|mail_from|translate:yform_email_from';
    $form_data[] = 'html|html2|</div><div class="col-md-6">';
    $form_data[] = 'text|mail_from_name|translate:yform_email_from_name';
    $form_data[] = 'html|html3|</div></div>';

    $form_data[] = 'html|html1|<div class="row"><div class="col-md-6">';
    $form_data[] = 'text|mail_reply_to|translate:yform_email_reply_to';
    $form_data[] = 'html|html2|</div><div class="col-md-6">';
    $form_data[] = 'text|mail_reply_to_name|translate:yform_email_reply_to_name';
    $form_data[] = 'html|html3|</div></div>';

    $form_data[] = 'text|subject|translate:yform_email_subject';
    $form_data[] = 'textarea|body|translate:yform_email_body|||{"class":"form-control codemirror","codemirror-mode":"php/htmlmixed"}';
    $form_data[] = 'textarea|body_html|translate:yform_email_body_html|||{"class":"form-control codemirror","codemirror-mode":"php/htmlmixed"}';
    $form_data[] = 'be_media|attachments|translate:yform_email_attachments|0|1';

    $form_data[] = 'datestamp|updatedate||||0';

    $yform = YForm::factory();
    $yform->setObjectparams('form_action', 'index.php?page=yform/email');
    $yform->setObjectparams('form_name', 'yform-email-template');

    $yform->setFormData(implode("\n", $form_data));
    $yform->setObjectparams('form_showformafterupdate', 1);

    $yform_clone = clone $yform;

    if ('edit' == $func) {
        $title = I18n::msg('yform_email_update');
        $yform->setValueField('submit', ['name' => 'submit', 'labels' => I18n::msg('yform_save') . ',' . I18n::msg('yform_save_apply'), 'values' => '1,2', 'no_db' => true, 'css_classes' => 'btn-save,btn-apply']);
        $yform->setHiddenField('template_id', $template_id);
        $yform->setHiddenField('func', $func);
        $yform->setActionField('db', [$table, "id=$template_id"]);
        $yform->setActionField('showtext', [Message::success(I18n::msg('yform_email_info_template_updated')), '', '', 1]);
        $yform->setObjectparams('main_id', $template_id);
        $yform->setObjectparams('main_where', "id=$template_id");
        $yform->setObjectparams('main_table', $table);
        $yform->setObjectparams('getdata', true);
    } else {
        $yform->setHiddenField('func', $func);
        $title = I18n::msg('yform_email_create');
        $yform->setValueField('submit', ['name' => 'submit', 'labels' => I18n::msg('yform_add'), 'values' => '1,btn-apply', 'no_db' => true, 'css_classes' => 'btn-save']);
        $yform->setActionField('db', [$table]);
        $yform->setActionField('showtext', [Message::success(I18n::msg('yform_email_info_template_added')), '', '', 1]);
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
        if ('edit' == $func) {
            if (2 == $submit_type) {
                $fragment = new Fragment();
                $fragment->setVar('class', 'edit', false);
                $fragment->setVar('title', $title);
                $fragment->setVar('body', $content, false);
                $content = $fragment->parse('core/page/section.php');

                $show_list = false;
            } else {
                $content = Message::success(I18n::msg('yform_email_info_template_updated'));
            }
        } else {
            // -> add
            if (2 == $submit_type) {
                $title = I18n::msg('yform_email_update');
                $template_id = (int) $yform->objparams['main_id'];
                $func = 'edit';

                $yform = $yform_clone;
                $yform->setHiddenField('func', $func);
                $yform->setHiddenField('template_id', $template_id);
                $yform->setActionField('db', [$table, "id=$template_id"]);
                $yform->setObjectparams('main_id', $template_id);
                $yform->setObjectparams('main_where', "id=$template_id");
                $yform->setObjectparams('main_table', $table);
                $yform->setObjectparams('getdata', true);
                $yform->setValueField('submit', ['name' => 'submit', 'labels' => I18n::msg('yform_save') . ',' . I18n::msg('yform_save_apply'), 'values' => '1,2', 'no_db' => true, 'css_classes' => 'btn-save,btn-apply']);
                $yform->executeFields();

                $content = $yform->executeActions();
                $fragment = new Fragment();
                $fragment->setVar('class', 'edit', false);
                $fragment->setVar('title', $title);
                $fragment->setVar('body', $content, false);
                $content = Message::success(I18n::msg('yform_email_info_template_added')) . $fragment->parse('core/page/section.php');

                $show_list = false;
            } else {
                $content = Message::success(I18n::msg('yform_email_info_template_added'));
            }
        }
    } else {
        $fragment = new Fragment();
        $fragment->setVar('class', 'edit', false);
        $fragment->setVar('title', $title);
        $fragment->setVar('body', $content, false);
        $content = $fragment->parse('core/page/section.php');

        $show_list = false;
    }
}

echo $content;

if ($show_list) {
    $link = '';
    $list = DataList::factory('select * from ' . $table, defaultSort: [
        'name' => 'asc',
    ]);
    // $list->setCaption(I18n::msg('yform_email_header_template_caption'));
    $list->addTableAttribute('summary', I18n::msg('yform_email_header_template_summary'));
    $list->addTableAttribute('class', 'table-striped');
    $list->addTableColumnGroup([40, 40, '*', 153, 153]);

    $tdIcon = '<i class="rex-icon rex-icon-template"></i>';
    $thIcon = '<a class="rex-link-expanded" href="' . $list->getUrl(['func' => 'add']) . '"' . Core::getAccesskey(I18n::msg('create_template'), 'add') . ' title="' . I18n::msg('create_template') . '"><i class="rex-icon rex-icon-add-template"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, [
        '<th class="rex-table-icon">###VALUE###</th>',
        '<td class="rex-table-icon">###VALUE###</td>',
    ]);

    $list->setColumnLabel('id', 'ID');
    $list->setColumnLayout('id', [
        '<th class="rex-small">###VALUE###</th>',
        '<td class="rex-small">###VALUE###</td>',
    ]);

    $list->setColumnLabel('name', I18n::msg('yform_email_header_template_description'));
    $list->setColumnParams('name', ['page' => $page, 'func' => 'edit', 'template_id' => '###id###']);

    $list->setColumnLabel('mail_from', I18n::msg('yform_email_header_template_mail_from'));

    $list->setColumnFormat('mail_from', 'custom', static function ($a) {
        return '###mail_from###<br />###mail_from_name###';
    });

    $list->setColumnLabel('mail_reply_to', I18n::msg('yform_email_header_template_mail_reply_to'));
    $list->setColumnFormat('mail_reply_to', 'custom', static function ($a) {
        return '###mail_reply_to###<br />###mail_reply_to_name###';
    });

    $list->setColumnLabel('subject', I18n::msg('yform_email_header_template_subject'));

    $list->removeColumn('mail_from_name');
    $list->removeColumn('mail_reply_to_name');
    $list->removeColumn('body');
    $list->removeColumn('body_html');
    $list->removeColumn('attachments');
    $list->removeColumn('updatedate');

    $list->addColumn(I18n::msg('yform_delete'), I18n::msg('yform_delete'));
    $list->setColumnParams(I18n::msg('yform_delete'), ['page' => $page, 'func' => 'delete', 'template_id' => '###id###'] + CsrfToken::factory($_csrf_key)->getUrlParams());
    $list->addLinkAttribute(I18n::msg('yform_delete'), 'onclick', 'return confirm(\' id=###id### ' . I18n::msg('yform_delete') . ' ?\')');

    $list->setNoRowsMessage(I18n::msg('yform_email_templates_not_found'));

    $content = $list->get();

    $fragment = new Fragment();
    $fragment->setVar('title', I18n::msg('yform_email_caption'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}
