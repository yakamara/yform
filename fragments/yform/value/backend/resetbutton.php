<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
?><button type="reset" class="btn btn-default<?= '' != trim($yfield->getElement(4)) ? ' ' . $yfield->getElement(4) : '' ?>" id="<?= $yfield->getFieldId() ?>" value="<?= \Redaxo\Core\View\escape($yfield->getValue()) ?>"><?= \Redaxo\Core\View\escape(\Redaxo\Core\Translation\I18n::translate($yfield->getValue())) ?></button>

