<?php

use Yakamara\YForm\Value\BackendManagerRelation;

/** @var BackendManagerRelation $this */
$fieldkey ??= '';
$forms ??= [];
$relationKey ??= '';
$class_group = trim('form-group ' . $this->getHTMLClass()); // . ' ' . $this->getWarningClass()
$id = sprintf('%u', crc32($this->params['form_name'])) . random_int(0, 10000) . $this->getId();
$fieldkey = 'y' . sha1($fieldkey . '-' . rex_escape($relationKey)); // no number first

echo '
    <div class="' . $class_group . '" id="' . $fieldkey . '" data-yform-be-relation-key="' . rex_escape($relationKey) . '" data-yform-be-relation-index="' . count($forms) . '">
        <label class="control-label" for="' . $this->getFieldId() . '">' . $this->getLabelStyle($this->getLabel()) . ' </label>
        <div data-yform-be-relation-item="' . $fieldkey . '" class="yform-be-relation-wrapper">';

$counter = 1;
foreach ($forms as $form) {
    $counterfieldkey = $fieldkey . '-' . $counter;
    echo '<div class="row" id="' . $counterfieldkey . '" data-yform-be-relation-item="' . $counterfieldkey . '">
                <div class="yform-be-relation-inline-form">' . $form . '</div>
            </div>';

    ++$counter;
}

echo '
        </div>
    </div>';
