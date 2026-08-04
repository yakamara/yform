<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
?><input type="hidden" name="<?= $fieldName ?? $yfield->getFieldName() ?>" id="<?= $yfield->getHTMLId() ?>" value="<?= \Redaxo\Core\View\escape($yfield->getValue()) ?>" />
