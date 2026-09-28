<?php

namespace Yakamara\YForm\Backend;

use JsonException;
use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Message;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Form\Definition\Forms;
use Yakamara\YForm\Form\Layout\Layout;
use Yakamara\YForm\Form\Layout\LayoutStore;
use Yakamara\YForm\Table\Tables;

use function is_array;
use function json_decode;
use function json_encode;
use function Redaxo\Core\View\escape;

use const JSON_THROW_ON_ERROR;

/**
 * Visual layout editor for tables and forms. The result is written to `ClassName.layout.json` next to the class.
 *
 * @internal
 */
final class DesignerPage
{
    /**
     * @param class-string $class
     * @param array<string, Field> $fields
     */
    private function __construct(
        private readonly string $class,
        private readonly string $title,
        private readonly array $fields,
        private readonly Layout $codeLayout,
        private readonly string $backUrl,
    ) {}

    public static function run(): string
    {
        $page = self::fromRequest();
        if (null === $page) {
            return Message::error(I18n::msg('yform_designer_target_missing'));
        }
        if ('post' === Request::requestMethod()) {
            $page->handlePost();
        }
        return $page->render();
    }

    private static function fromRequest(): ?self
    {
        $tableName = Request::request('table', 'string', '');
        if ('' !== $tableName && null !== $table = Tables::find($tableName)) {
            return new self($table::class, $table->label(), $table->getFields(), $table->layout(new Layout()), Url::backendPage('yform/tables'));
        }
        $formName = Request::request('form', 'string', '');
        if ('' !== $formName && null !== $form = Forms::find($formName)) {
            return new self($form::class, $form->label(), $form->getFields(), $form->layout(new Layout()), Url::backendPage('yform/forms'));
        }
        return null;
    }

    /** Saves or resets via fetch; answers with JSON. */
    private function handlePost(): never
    {
        if (!CsrfToken::factory('yform_designer')->isValid()) {
            Response::setStatus(Response::HTTP_FORBIDDEN);
            Response::sendJson(['error' => I18n::rawMsg('csrf_token_invalid')]);
            exit;
        }
        try {
            if ('reset' === Request::post('action', 'string', '')) {
                LayoutStore::delete($this->class);
                Response::sendJson(['message' => I18n::rawMsg('yform_designer_reset_done'), 'layout' => $this->effectiveLayout()->toArray(), 'source' => 'code']);
                exit;
            }
            $data = json_decode(Request::post('layout', 'string', ''), true, 32, JSON_THROW_ON_ERROR);
            $layout = Layout::fromArray(is_array($data) ? $data : [])->resolve($this->editableFields());
            LayoutStore::save($this->class, $layout);
            Response::sendJson(['message' => I18n::rawMsg('yform_designer_saved', $this->relativePath()), 'layout' => $layout->toArray(), 'source' => 'file']);
        } catch (JsonException|Throwable $e) {
            Response::setStatus(Response::HTTP_BAD_REQUEST);
            Response::sendJson(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function render(): string
    {
        $fields = [];
        foreach ($this->editableFields() as $name => $field) {
            $fields[] = [
                'name' => $name,
                'label' => $field->translatedLabel(),
                'type' => new ReflectionClass($field)->getShortName(),
                'required' => $field->required,
            ];
        }

        $config = [
            'fields' => $fields,
            'layout' => $this->effectiveLayout()->toArray(),
            'source' => LayoutStore::exists($this->class) ? 'file' : 'code',
            'writable' => LayoutStore::isWritable($this->class),
            'csrf' => CsrfToken::factory('yform_designer')->getValue(),
            'url' => Url::currentBackendPage([
                'table' => Request::request('table', 'string', '') ?: null,
                'form' => Request::request('form', 'string', '') ?: null,
            ]),
            'i18n' => [
                'tab' => I18n::rawMsg('yform_designer_tab'),
                'tab_add' => I18n::rawMsg('yform_designer_tab_add'),
                'tab_remove' => I18n::rawMsg('yform_designer_tab_remove'),
                'section_add' => I18n::rawMsg('yform_designer_section_add'),
                'section_remove' => I18n::rawMsg('yform_designer_section_remove'),
                'legend_placeholder' => I18n::rawMsg('yform_designer_legend_placeholder'),
                'new_row' => I18n::rawMsg('yform_designer_new_row'),
                'palette' => I18n::rawMsg('yform_designer_palette'),
                'palette_empty' => I18n::rawMsg('yform_designer_palette_empty'),
                'width' => I18n::rawMsg('yform_designer_width'),
                'remove' => I18n::rawMsg('yform_designer_remove'),
                'save' => I18n::rawMsg('yform_save'),
                'reset' => I18n::rawMsg('yform_designer_reset'),
                'reset_confirm' => I18n::rawMsg('yform_designer_reset_confirm'),
                'source_file' => I18n::rawMsg('yform_designer_source_file', $this->relativePath()),
                'source_code' => I18n::rawMsg('yform_designer_source_code'),
                'not_writable' => I18n::rawMsg('yform_designer_not_writable', $this->relativePath()),
                'unsaved' => I18n::rawMsg('yform_designer_unsaved'),
                'row_overflow' => I18n::rawMsg('yform_designer_row_overflow'),
            ],
        ];

        return '<section class="rex-page-section"><div class="panel panel-default">'
            . '<header class="panel-heading"><div class="panel-title">' . escape(I18n::msg('yform_designer')) . ': ' . escape($this->title)
            . ' <small><code>' . escape($this->class) . '</code></small></div></header>'
            . '<div class="panel-body"><div id="yform-designer" data-config="' . escape(json_encode($config, JSON_THROW_ON_ERROR)) . '"></div></div>'
            . '<footer class="panel-footer"><a href="' . escape($this->backUrl) . '">&larr; ' . escape(I18n::msg('yform_back')) . '</a></footer>'
            . '</div></section>';
    }

    /** Fields that can be placed (all fields; stored and layout-only ones). */
    private function editableFields(): array
    {
        return $this->fields;
    }

    private function effectiveLayout(): Layout
    {
        return (LayoutStore::load($this->class) ?? $this->codeLayout)->resolve($this->editableFields());
    }

    private function relativePath(): string
    {
        return Path::relative(LayoutStore::path($this->class), Path::base());
    }
}
