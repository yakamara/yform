<?php

/**
 * @var rex_yform_value_abstract $this
 * @psalm-scope-this rex_yform_value_abstract
 */

?><button type="reset" class="btn btn-default<?= '' != trim($this->getElement(4)) ? ' ' . $this->getElement(4) : '' ?>" id="<?= $this->getFieldId() ?>" value="<?= rex_escape($this->getValue()) ?>"><?= rex_escape(rex_i18n::translate($this->getValue())) ?></button>

