<?php

namespace Yakamara\YForm\Field\Relation;

/** What happens with referencing records when the referenced record is deleted. */
enum OnDelete
{
    /** Deleting is refused while references exist. */
    case Restrict;
    /** The reference is cleared. */
    case SetNull;
    /** Referencing records are deleted as well (with their hooks). */
    case Cascade;
}
