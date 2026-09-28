<?php

namespace Yakamara\YForm\Field;

use Override;

/**
 * Value carried in a hidden input (frontend forms), e.g. a campaign key: `Hidden::new('source')->default('newsletter')`.
 *
 * The submitted value is untrusted: never use a hidden field for values the user must not change.
 */
class Hidden extends Text
{
    #[Override]
    protected function init(): void
    {
        $this->listed = false;
    }

    #[Override]
    public function wrapped(): bool
    {
        return false;
    }
}
