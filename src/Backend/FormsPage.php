<?php

namespace Yakamara\YForm\Backend;

use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Component\ColumnType;
use Redaxo\Core\View\Component\Section;
use Redaxo\Core\View\Component\Table as TableComponent;
use Redaxo\Core\View\Component\TableColumn;
use Redaxo\Core\View\Html;
use Redaxo\Core\View\HtmlAttributes;
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
            $html = new Form($form->name, $form->fields, $form->layout, Theme::backend())->render();
            $preview = (string) new Section(I18n::msg('yform_preview') . ': ' . $definition->label(), Html::raw(Message::info(I18n::msg('yform_preview_hint')) . $html))->render();
        }

        $rows = [];
        foreach ($forms as $name => $definition) {
            $file = (string) new ReflectionClass($definition)->getFileName();
            $rows[] = [
                'icon' => Html::raw('<i class="rex-icon fa-brands fa-wpforms"></i>'),
                'form' => Html::raw('<strong>' . escape($definition->label()) . '</strong><br><code>' . escape($name) . '</code>'),
                'definition' => Html::raw('<code>' . escape($definition::class) . '</code><br><small class="rex-u-text-muted">' . escape(Path::relative($file, Path::base())) . '</small>'),
                'fields' => count($definition->getFields()),
                'actions' => Html::raw(self::actionList($definition)),
                'module' => $definition->definition->module ? Html::raw('<i class="rex-icon fa-check" title="' . I18n::msg('yform_module') . '"></i>') : null,
                'usage' => Html::raw('<code>Forms::get(\'' . escape($name) . '\')-&gt;run()</code>'),
                'preview' => Html::raw('<a href="' . escape(Url::currentBackendPage(['preview' => $name])) . '"><i class="rex-icon fa-regular fa-eye"></i> ' . I18n::msg('yform_preview') . '</a>'),
                'designer' => Html::raw('<a href="' . escape(Url::backendPage('yform/designer', ['form' => $name])) . '"><i class="rex-icon fa-th-large"></i> ' . I18n::msg('yform_designer') . '</a>'
                    . (LayoutStore::exists($definition::class) ? ' <span class="rex-badge" data-variant="info">JSON</span>' : '')),
            ];
        }

        return $preview . new Section(I18n::msg('yform_forms'), content: new TableComponent([
            TableColumn::forKey('', 'icon', ColumnType::Icon),
            TableColumn::forKey(I18n::msg('yform_form'), 'form'),
            TableColumn::forKey(I18n::msg('yform_definition'), 'definition'),
            TableColumn::forKey(I18n::msg('yform_fields'), 'fields'),
            TableColumn::forKey(I18n::msg('yform_actions_list'), 'actions'),
            TableColumn::forKey(I18n::msg('yform_module'), 'module'),
            TableColumn::forKey(I18n::msg('yform_usage'), 'usage'),
            TableColumn::forKey(I18n::msg('yform_actions'), 'preview', ColumnType::Action),
            TableColumn::forKey(null, 'designer', ColumnType::Action),
        ], $rows, attributes: new HtmlAttributes(['data-hover' => true])))->render();
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
