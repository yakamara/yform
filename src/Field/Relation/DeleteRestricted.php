<?php

namespace Yakamara\YForm\Field\Relation;

use RuntimeException;

/** The record is still referenced by records of another table (`OnDelete::Restrict`). */
final class DeleteRestricted extends RuntimeException {}
