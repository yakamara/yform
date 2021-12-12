<?php

/**
 * @var rex_yform_value_abstract $this
 * @psalm-scope-this rex_yform_value_abstract
 */

?><input type="hidden" name="<?php echo $fieldName ?? $this->getFieldName(); ?>" id="<?php echo $this->getHTMLId() ?>" value="<?php echo rex_escape($this->getValue()) ?>" />
