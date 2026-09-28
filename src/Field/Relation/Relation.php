<?php

namespace Yakamara\YForm\Field\Relation;

use Yakamara\YForm\Data\Collection;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Table\Table;

/** A field pointing to records of another table. */
interface Relation
{
    public function targetTable(): Table;

    /** @return Record|Collection<Record>|null */
    public function related(Record $record): Record|Collection|null;
}
