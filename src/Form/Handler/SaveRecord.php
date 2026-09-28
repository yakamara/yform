<?php

namespace Yakamara\YForm\Form\Handler;

use Closure;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;
use Yakamara\YForm\Validation\ValidationException;

/**
 * Writes the submission into a table: every form field with a same-named table field is copied.
 *
 * `new SaveRecord(ContactTable::class, fn (Record $r, Submission $s) => $r->set('source', 'web'))`
 */
final readonly class SaveRecord implements Handler
{
    /**
     * @param class-string<Table>|string $table
     * @param (Closure(Record, Submission): void)|null $prepare adjusts the record before saving
     */
    public function __construct(
        public string $table,
        public ?Closure $prepare = null,
    ) {}

    public function handle(Submission $submission): void
    {
        $table = Tables::get($this->table);
        $record = $table->newRecord();
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
