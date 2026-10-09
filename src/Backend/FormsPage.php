<?php

namespace Yakamara\YForm\Backend;

use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Message;
use ReflectionClass;
use Yakamara\YForm\Form\Definition\FormDefinition;
use Yakamara\YForm\Form\Definition\Forms;
use Yakamara\YForm\Form\Form;
use Yakamara\YForm\Form\Layout\LayoutStore;
use Yakamara\YForm\Form\Theme\Theme;

use function count;
use function Redaxo\Core\View\escape;

/**
 * Admin overview of the form definitions with designer link and a backend preview.
 *
 * @internal
 */
final class FormsPage
{
    public static function run(): string
    {
        $forms = Forms::all();
        if ([] === $forms) {
            return Message::info(I18n::msg('yform_forms_empty'));
        }

        $preview = '';
        $previewName = Request::get('preview', 'string', '');
        if ('' !== $previewName && null !== $definition = Forms::find($previewName)) {
            $form = $definition->build();
            $form->csrf = false;
            $form->readonly = false;
            // The preview must not trigger actions (mails, records): it only renders.
            $form->actionUrl = '#';
            $html = new Form($form->name, $form->fields, $form->layout, Theme::bootstrap3())->render();
            $preview = '<section class="rex-page-section"><div class="panel panel-default">'
                . '<header class="panel-heading"><div class="panel-title">' . escape(I18n::msg('yform_preview')) . ': ' . escape($definition->label()) . '</div></header>'
                . '<div class="panel-body">' . Message::info(I18n::msg('yform_preview_hint')) . $html . '</div></div></section>';
        }

        $rows = '';
        foreach ($forms as $name => $definition) {
            $file = (string) new ReflectionClass($definition)->getFileName();
            $rows .= '<tr>'
                . '<td class="rex-table-icon"><i class="rex-icon fa-wpforms"></i></td>'
                . '<td><strong>' . escape($definition->label()) . '</strong><br><code>' . escape($name) . '</code></td>'
                . '<td><code>' . escape($definition::class) . '</code><br><small class="text-muted">' . escape(Path::relative($file, Path::base())) . '</small></td>'
                . '<td>' . count($definition->getFields()) . '</td>'
                . '<td>' . self::actionList($definition) . '</td>'
                . '<td>' . ($definition->definition->module ? '<i class="rex-icon fa-check" title="' . escape(I18n::msg('yform_module')) . '"></i>' : '') . '</td>'
                . '<td><code>Forms::get(\'' . escape($name) . '\')-&gt;run()</code></td>'
                . '<td class="rex-table-action"><a href="' . escape(Url::currentBackendPage(['preview' => $name])) . '"><i class="rex-icon fa-eye"></i> ' . escape(I18n::msg('yform_preview')) . '</a></td>'
                . '<td class="rex-table-action"><a href="' . escape(Url::backendPage('yform/designer', ['form' => $name])) . '"><i class="rex-icon fa-th-large"></i> ' . escape(I18n::msg('yform_designer')) . '</a>'
                . (LayoutStore::exists($definition::class) ? ' <span class="label label-info">JSON</span>' : '') . '</td>'
                . '</tr>';
        }

        return $preview . '<section class="rex-page-section"><div class="panel panel-default">'
            . '<header class="panel-heading"><div class="panel-title">' . escape(I18n::msg('yform_forms')) . '</div></header>'
            . '<table class="table table-hover"><thead><tr><th class="rex-table-icon"></th>'
            . '<th>' . escape(I18n::msg('yform_form')) . '</th><th>' . escape(I18n::msg('yform_definition')) . '</th>'
            . '<th>' . escape(I18n::msg('yform_fields')) . '</th><th>' . escape(I18n::msg('yform_actions_list')) . '</th>'
            . '<th>' . escape(I18n::msg('yform_module')) . '</th><th>' . escape(I18n::msg('yform_usage')) . '</th>'
            . '<th class="rex-table-action" colspan="2">' . escape(I18n::msg('yform_actions')) . '</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table></div></section>';
    }

    private static function actionList(FormDefinition $definition): string
    {
        $names = [];
        foreach ($definition->actions() as $action) {
            $names[] = '<code>' . escape(new ReflectionClass($action)->getShortName()) . '</code>';
        }
        return [] === $names ? '–' : implode(' ', $names);
    }
}
