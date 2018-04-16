<?php
$getAttributes = function ($element, array $attributes = [], $value = null, $label = null, array $directAttributes = []) {
    $additionalAttributes = $this->getElement($element);
    if ($additionalAttributes) {
        if (is_callable($additionalAttributes)) {
            $additionalAttributes = call_user_func($additionalAttributes, $attributes, $value, $label);
        } elseif (!is_array($additionalAttributes)) {
            $additionalAttributes = json_decode(trim($additionalAttributes), true);
        }
        if ($additionalAttributes && is_array($additionalAttributes)) {
            foreach ($additionalAttributes as $attribute => $attributeValue) {
                $attributes[$attribute] = $attributeValue;
            }
        }
    }

    foreach ($directAttributes as $attribute) {
        if (($element = $this->getElement($attribute))) {
            $attributes[$attribute] = $element;
        }
    }

    if (null !== $value && isset($attributes[$value]) && is_array($attributes[$value])) {
        foreach ($attributes[$value] as $attribute => $attributeValue) {
            $attributes[$attribute] = $attributeValue;
        }
    }
    $return = [];
    foreach ($attributes as $attribute => $attributeValue) {
        if (is_array($attributeValue)) {
            continue;
        }
        $return[] = $attribute.'="'.htmlspecialchars($attributeValue).'"';
    }

    return $return;
};

$widget = '';

$notices = [];
if ($this->getElement('notice') != '') {
    $notices[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">'.rex_i18n::translate($this->params['warning_messages'][$this->getId()], false).'</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block">'.implode('<br />', $notices).'</p>';
}

if ($options['expanded']) {
    // Aufbau von  `radio` oder `checkbox`
    $elementAttributes = $getAttributes('attributes', ['class' => trim(($options['multiple'] ? 'checkbox' : 'radio').' '.$this->getWarningClass())]);

    $choiceElements = [];
    $preferredElements = [];
    foreach ($options['choices'] as $label => $value) {
        $choiceAttributes = [
            'id' => $this->getFieldId(),
            'name' => $this->getFieldName(),
            'type' => 'radio',
        ];

        if ($options['multiple']) {
            $choiceAttributes['name'] .= '[]';
            $choiceAttributes['type'] = 'checkbox';
        }

        $choiceAttributes['value'] = $value;
        if (in_array((string) $value, $this->getValue(), true)) {
            $choiceAttributes['checked'] = 'checked';
        }

        $choiceAttributes = $getAttributes('choice_attributes', $choiceAttributes, $value, $label, ['autofocus', 'disabled', 'required']);

        $element = sprintf('<div %s><label><input %s /><i class="form-helper"></i>%s</label></div>', implode(' ', $elementAttributes), implode(' ', $choiceAttributes), $this->getLabelStyle($label));

        if (in_array($value, $options['preferred_choices'])) {
            $preferredElements[] = $element;
            continue;
        }
        $choiceElements[] = $element;
    }

    if (count($preferredElements)) {
        $choiceElements = array_merge($preferredElements, $choiceElements);
    }

    $groupAttributes = $getAttributes('group_attributes', ['class' => trim('form-check-group')]);

    $widgetLabel = '';
    if ($this->getLabel() != '') {
        $widgetLabel = sprintf('<label class="form-control-label" for="%s">%s</label>', $this->getFieldId(), $this->getLabelStyle($this->getLabel()));
    }

    $widget =
        '<div '.implode(' ', $groupAttributes).'>
            '.$widgetLabel.'
            '.implode('', $choiceElements).'
            '.$notice.'
        </div>';
} else {
    // Aufbau eines `select`
    $elementAttributes = [
        'class' => 'form-control',
        'id' => $this->getFieldId(),
        'name' => $this->getFieldName(),
    ];

    if ($options['multiple']) {
        $elementAttributes['name'] .= '[]';
        $elementAttributes['multiple'] = 'multiple';
        $elementAttributes['size'] = count($options['choices']);
    }

    $groupAttributes = $getAttributes('group_attributes', ['class' => trim('form-group '.$this->getWarningClass())]);
    $elementAttributes = $getAttributes('attributes', $elementAttributes, null, null, ['autocomplete', 'disabled', 'pattern', 'readonly', 'required', 'size']);

    $choiceElements = [];
    $preferredElements = [];
    if (count($options['group_choices'])) {
        $choiceOptGroups = [];
        $preferredOptGroups = [];
        foreach ($options['group_choices'] as $optGroupLabel => $choices) {
            foreach ($choices as $label => $value) {
                $choiceAttributes = [
                    'value' => $value,
                ];
                if (in_array((string) $value, $this->getValue(), true)) {
                    $choiceAttributes['selected'] = 'selected';
                }
                $choiceAttributes = $getAttributes('choice_attributes', $choiceAttributes, $value, $label);
                $option = sprintf('<option %s>%s</option>', implode(' ', $choiceAttributes), $this->getLabelStyle($label));

                if (in_array($value, $options['preferred_choices'])) {
                    $preferredOptGroups[$optGroupLabel][] = $option;
                    continue;
                }
                $choiceOptGroups[$optGroupLabel][] = $option;
            }
        }

        if ($preferredOptGroups) {
            foreach ($preferredOptGroups as $optGroupLabel => $optGroupOptions) {
                $preferredElements[] = sprintf('<optgroup label="%s">%s</optgroup>', rex_escape($optGroupLabel), implode('', $optGroupOptions));
            }
        }
        if ($choiceOptGroups) {
            foreach ($choiceOptGroups as $optGroupLabel => $optGroupOptions) {
                $choiceElements[] = sprintf('<optgroup label="%s">%s</optgroup>', rex_escape($optGroupLabel), implode('', $optGroupOptions));
            }
        }
    } else {
        foreach ($options['choices'] as $label => $value) {
            $choiceAttributes = [
                'value' => $value,
            ];
            if (in_array((string) $value, $this->getValue(), true)) {
                $choiceAttributes['selected'] = 'selected';
            }
            $choiceAttributes = $getAttributes('choice_attributes', $choiceAttributes, $value, $label);
            $option = sprintf('<option %s>%s</option>', implode(' ', $choiceAttributes), $this->getLabelStyle($label));

            if (in_array($value, $options['preferred_choices'])) {
                $preferredElements[] = $option;
                continue;
            }
            $choiceElements[] = $option;
        }
    }

    if (count($preferredElements)) {
        $preferredElements[] = '<option disabled="disabled">-------------------</option>';
        $choiceElements = array_merge($preferredElements, $choiceElements);
    }

    if (!$options['multiple'] && $options['placeholder']) {
        array_unshift($choiceElements, sprintf('<option value="" class="placeholder">%s</option>', rex_escape($options['placeholder'])));
    }

    $widgetLabel = '';
    if ($this->getLabel() != '') {
        $widgetLabel = sprintf('<label class="form-control-label" for="%s">%s</label>', $this->getFieldId(), $this->getLabelStyle($this->getLabel()));
    }

    $widget =
        '<div '.implode(' ', $groupAttributes).'>
            '.$widgetLabel.'
            <select '.implode(' ', $elementAttributes).'>
                '.implode('', $choiceElements).'
            </select>
            '.$notice.'
        </div>';
}

echo $widget;
