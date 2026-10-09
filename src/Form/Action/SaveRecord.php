<?php

namespace Yakamara\YForm\Form\Action;

use Closure;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;
use Yakamara\YForm\Validation\ValidationException;

/**
 * Writes the submission into a table: every form field with a same-named table field is copied. The saved record
 * is available to later actions as `$submission->record` and `{id}` placeholder.
 *
 * `new SaveRecord(ContactTable::class, fn (Record $r, Submission $s) => $r->set('source', 'web'))`
 */
final class SaveRecord extends Action
{
    /**
     * @param class-string<Table>|string $table
     * @param (Closure(Record, Submission): void)|null $prepare adjusts the record before saving
     */
    public function __construct(
        public readonly string $table,
        public readonly ?Closure $prepare = null,
    ) {}

    public function execute(Submission $submission): void
    {
        $record = Tables::get($this->table)->newRecord();
        foreach ($submission->form->fields as $name => $field) {
            if ($field->storesValue() && $record->has($name)) {
                $record->set($name, $submission->value($name));
            }
        }
        if (null !== $this->prepare) {
            ($this->prepare)($record, $submission);
        }

        try {
            $record->save();
        } catch (ValidationException $e) {
            // Table rules the form does not know (e.g. uniqueness) — show them at the form fields.
            foreach ($e->errors->toArray() as $field => $messages) {
                foreach ($messages as $message) {
                    $submission->form->errors->add($field, $message);
                }
            }
            throw $e;
        }
        $submission->record = $record;
    }
}
