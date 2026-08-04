<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$fieldkey ??= '';
$forms ??= [];
$relationKey ??= '';
$class_group = trim('form-group ' . $yfield->getHTMLClass()); // . ' ' . $yfield->getWarningClass()
$id = sprintf('%u', crc32($yfield->params['form_name'])) . random_int(0, 10000) . $yfield->getId();
$fieldkey = 'y' . sha1($fieldkey . '-' . \Redaxo\Core\View\escape($relationKey)); // no number first

echo '
    <div class="' . $class_group . '" id="' . $fieldkey . '" data-yform-be-relation-key="' . \Redaxo\Core\View\escape($relationKey) . '" data-yform-be-relation-index="' . count($forms) . '">
        <label class="control-label" for="' . $yfield->getFieldId() . '">' . $yfield->getLabelStyle($yfield->getLabel()) . ' </label>
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
