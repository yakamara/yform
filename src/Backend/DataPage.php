<?php

namespace Yakamara\YForm\Backend;

use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Message;
use Redaxo\Core\View\View;
use Throwable;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Form\Form;
use Yakamara\YForm\Form\Theme\Theme;
use Yakamara\YForm\Permission\Access;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;

use function Redaxo\Core\View\escape;

/**
 * Controller of the backend data page (`yform/data&table=…`): list, add, edit, clone, delete.
 *
 * @internal
 */
final class DataPage
{
    private readonly RecordList $list;
    private readonly bool $canEdit;

    private function __construct(
        private readonly Table $table,
    ) {
        $this->canEdit = Access::canEdit($table);
        $this->list = new RecordList($table, $this->canEdit);
    }

    public static function run(): string
    {
        $table = Tables::find(Request::request('table', 'string', ''));
        if (null === $table || !Access::canView($table)) {
            return View::title(I18n::msg('yform')) . Message::error(I18n::msg('yform_table_not_found'));
        }

        return new self($table)->dispatch();
    }

    private function dispatch(): string
    {
        $title = View::title('<i class="rex-icon ' . escape($this->table->icon()) . '"></i> ' . escape($this->table->label()), '');
        $func = Request::request('func', 'string', '');

        return $title . $this->flash() . match ($func) {
            'add' => $this->canEdit ? $this->edit($this->table->newRecord()) : $this->denied(),
            'edit' => $this->editExisting(),
            'clone' => $this->canEdit ? $this->cloneExisting() : $this->denied(),
            'delete' => $this->delete(),
            default => $this->list->render(),
        };
    }

    private function editExisting(): string
    {
        $record = $this->list->baseQuery()->where('id', Request::request('id', 'int', 0))->first();
        if (null === $record) {
            return Message::error(I18n::msg('yform_record_not_found')) . $this->list->render();
        }
        return $this->edit($record);
    }

    /** Add form prefilled with a copy of the record; the submission is posted back to `func=clone`. */
    private function cloneExisting(): string
    {
        $source = $this->list->baseQuery()->where('id', Request::request('id', 'int', 0))->first();
        if (null === $source) {
            return Message::error(I18n::msg('yform_record_not_found')) . $this->list->render();
        }
        return $this->edit($source->replicate(), $source);
    }

    private function edit(Record $record, ?Record $source = null): string
    {
        $isNew = $record->isNew();
        $form = Form::forRecord($record, Theme::backend());
        $form->actionUrl = null !== $source
            ? $this->list->url(['func' => 'clone', 'id' => $source->id])
            : $this->list->url(['func' => $isNew ? 'add' : 'edit', 'id' => $record->id]);
        $form->readonly = !$this->canEdit;

        if ($this->canEdit) {
            $form->addButton('apply', I18n::rawMsg('yform_apply'));
        }

        try {
            if ($form->handleRequest()) {
                $url = 'apply' === $form->action
                    ? $this->list->url(['func' => 'edit', 'id' => $record->id, 'msg' => 'saved'])
                    : $this->list->url(['msg' => $isNew ? 'added' : 'saved']);
                Response::sendRedirect($url);
            }
        } catch (Throwable $e) {
            return Message::error(escape($e->getMessage())) . $this->panel($record, $form, $source);
        }

        return $this->panel($record, $form, $source);
    }

    private function panel(Record $record, Form $form, ?Record $source = null): string
    {
        $heading = match (true) {
            null !== $source => I18n::rawMsg('yform_clone_record', escape($source->label()), (int) $source->id),
            $record->isNew() => I18n::msg('yform_add_record'),
            default => I18n::rawMsg($this->canEdit ? 'yform_edit_record' : 'yform_view_record', escape($record->label()), (int) $record->id),
        };

        $panel = '<section class="rex-page-section"><div class="panel panel-edit yform-edit">'
            . '<header class="panel-heading"><div class="panel-title">' . $heading . '</div></header>'
            . '<div class="panel-body">' . $form->render() . '</div>'
            . '</div></section>';

        return Message::info('<a href="' . escape($this->list->url()) . '">' . I18n::msg('yform_back_to_list') . '</a>')
            . new SearchPanel($this->list)->wrap($panel);
    }

    private function delete(): string
    {
        if (!$this->canEdit) {
            return $this->denied();
        }
        if ('post' !== Request::requestMethod() || !CsrfToken::factory('yform_delete_' . $this->table->name)->isValid()) {
            return Message::error(I18n::msg('csrf_token_invalid')) . $this->list->render();
        }
        $record = $this->list->baseQuery()->where('id', Request::request('id', 'int', 0))->first();
        if (null === $record) {
            return Message::error(I18n::msg('yform_record_not_found')) . $this->list->render();
        }
        try {
            $record->delete();
        } catch (Throwable $e) {
            return Message::error(escape($e->getMessage())) . $this->list->render();
        }
        Response::sendRedirect($this->list->url(['msg' => 'deleted']));
    }

    private function flash(): string
    {
        return match (Request::get('msg', 'string', '')) {
            'added' => Message::success(I18n::msg('yform_record_added')),
            'saved' => Message::success(I18n::msg('yform_record_saved')),
            'deleted' => Message::success(I18n::msg('yform_record_deleted')),
            default => '',
        };
    }

    private function denied(): string
    {
        return Message::error(I18n::msg('yform_permission_denied')) . $this->list->render();
    }
}
