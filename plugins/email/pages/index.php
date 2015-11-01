<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform_email_templates'));

$table = rex::getTablePrefix() . 'yform_email_template';
$bezeichner = rex_i18n::msg('yform_email_template');
$csuchfelder = array('name', 'mail_from', 'mail_subject', 'body');

$func = rex_request('func', 'string', '');
$page = rex_request('page', 'string', '');
$template_id = rex_request('template_id', 'int');
$message = '';
$show_list = true;

if ($func == 'edit' || $func == 'add') {

    echo rex_view::info('<p>Durch folgende Markierungen <b>###field###</b> kann man die in den Formularen eingegebenen Felder hier im E-Mail Template verwenden. Weiterhin sind
    alle REDAXO Variablen wie $REX["SERVER"] als <b>###REX_SERVER###</b> verwendbar. Urlencoded, z.b. für Links, bekommt man diese Werte über <b>+++field+++</b></p>');

    $form_data = [];
    $form_data[] = 'text|name|translate:yform_email_key';
    $form_data[] = 'validate|empty|name|Bitte key eintragen';

    $form_data[] = 'text|mail_from|translate:yform_email_from';
    $form_data[] = 'text|mail_from_name|translate:yform_email_from_name';
    $form_data[] = 'text|subject|translate:yform_email_subject';
    $form_data[] = 'textarea|body|translate:yform_email_body';
    $form_data[] = 'be_medialist|attachments|translate:yform_email_attachments';

    $form_data[] = 'action|db|'.$table; // |[where(id=2)/main_where]';

    $form_data[]  = 'action|showtext|Vielen Dank|||1'; //  (plaintext/html/textile)

    $yform = rex_yform::factory();
    $yform->setObjectparams('form_action','index.php?page=yform/email/index&func='.$func);

    $yform->setObjectparams('main_table', $table);

    $yform->setFormData(implode("\n",$form_data));

    $content = $yform->getForm();

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', rex_i18n::msg('yform_email_create'));
    $fragment->setVar('body', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;

} else if ($func == 'delete') {

    $query = "delete from $table where id='" . $template_id . "' ";
    $delsql = rex_sql::factory();
    $delsql->setQuery($query);

    $message = rex_view::info(rex_i18n::msg('yform_email_info_template_deleted'));

}

if ($show_list) {

    $add_sql = ' ORDER BY name';
    $link  = '';

    $sql = "select * from $table " . $add_sql;

    $list = rex_list::factory($sql);
    $list->setCaption(rex_i18n::msg('yform_email_header_template_caption'));
    $list->addTableAttribute('summary', rex_i18n::msg('yform_email_header_template_summary'));
    $list->addTableAttribute('class', 'table-striped');
    $list->addTableColumnGroup(array(40, 40, '*', 153, 153));

    $tdIcon = '<i class="rex-icon rex-icon-template"></i>';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '"' . rex::getAccesskey(rex_i18n::msg('create_template'), 'add') . ' title="' . rex_i18n::msg('create_template') . '"><i class="rex-icon rex-icon-add-template"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);

    $list->setColumnLabel('id', 'ID');
    $list->setColumnLayout('id',  array('<th class="rex-small">###VALUE###</th>', '<td class="rex-small">###VALUE###</td>'));

    $list->setColumnLabel('name', rex_i18n::msg('yform_email_header_template_description'));
    $list->setColumnParams('name', array('page' => $page, 'func' => 'edit', 'template_id' => '###id###'));

    $list->setColumnLabel('mail_from', rex_i18n::msg('yform_email_header_template_mail_from'));
    $list->setColumnLabel('mail_from_name', rex_i18n::msg('yform_email_header_template_mail_from_name'));
    $list->setColumnLabel('subject', rex_i18n::msg('yform_email_header_template_subject'));

    $list->removeColumn('body');
    $list->removeColumn('body_html');
    $list->removeColumn('attachments');

    $list->addColumn(rex_i18n::msg('yform_delete'), rex_i18n::msg('yform_delete'));
    $list->setColumnParams(rex_i18n::msg('yform_delete'), array('page' => $page, 'func' => 'delete', 'template_id' => '###id###'));
    $list->addLinkAttribute(rex_i18n::msg('yform_delete'), 'onclick', 'return confirm(\' id=###id### ' . rex_i18n::msg('yform_delete') . ' ?\')');

    $list->setNoRowsMessage(rex_i18n::msg('yform_email_templates_not_found'));

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('yform_email_caption'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $message;
    echo $content;

}
