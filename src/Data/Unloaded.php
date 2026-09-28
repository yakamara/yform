<?php

namespace Yakamara\YForm\Data;

/**
 * Marker for record values that are loaded on first access (relations without own column).
 *
 * @internal
 */
enum Unloaded
{
    case Value;
}
