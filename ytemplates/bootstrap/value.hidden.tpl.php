<?php

/**
 * @var rex_yform_value_abstract $this
 * @psalm-scope-this rex_yform_value_abstract
 */

?><input type="hidden" name="<?= $fieldName ?? $this->getFieldName() ?>" id="<?= $this->getHTMLId() ?>" value="<?= rex_escape($this->getValue()) ?>" />
