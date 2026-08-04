<?php

use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Fragment;
use Redaxo\Core\View\View;
use Yakamara\YForm\YForm;

/**
 * @var Addon $this
 * @psalm-scope-this Addon
 */

echo View::title($this->i18n('yform'));

/*
 * REDAXO 5 offered a button here that inserted a "YForm Formbuilder" module into `rex_module`, so an
 * editor could drop a yform form into an article. REDAXO 6 has neither that table nor module CRUD —
 * modules are PHP classes marked with #[AsModule] and discovered from the project's src/ — and the
 * REDAXO 5 module body consisted almost entirely of REX_VALUE[…] placeholders, which is exactly the
 * REX_VAR system REDAXO 6 removed. There is therefore nothing to install; a form is rendered from a
 * module class instead:
 *
 *     #[AsModule('yform_form', 'YForm Formular')]
 *     final class YFormModule extends AbstractModule
 *     {
 *         public function getOutput(): string
 *         {
 *             return (new YForm())->setObjectparams('form_action', …)
 *                 ->setFormData($this->getValue('form'))
 *                 ->getForm();
 *         }
 *     }
 */

$content = I18n::rawMsg('yform_description_all');

$fragment = new Fragment();
$fragment->setVar('title', $this->i18n('description'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

$fragment = new Fragment();
$fragment->setVar('title', $this->i18n('description_type_heading'), false);
$fragment->setVar('body', YForm::showHelp(), false);
echo $fragment->parse('core/page/section.php');
