<?php

use Yakamara\YForm\Value\AbstractValue;

/** @var AbstractValue $this */

?><input type="hidden" name="<?= $fieldName ?? $this->getFieldName() ?>" id="<?= $this->getHTMLId() ?>" value="<?= rex_escape($this->getValue()) ?>" />
