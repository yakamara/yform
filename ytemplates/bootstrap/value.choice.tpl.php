<?php

$getAttributes = function ($element, array $attributes) {
    $additionalAttributes = $this->getElement($element);
    if ($additionalAttributes) {
        if (!is_array($additionalAttributes)) {
            $additionalAttributes = json_decode(trim($additionalAttributes), true);
        }
        if ($additionalAttributes && is_array($additionalAttributes)) {
            foreach ($additionalAttributes as $attribute => $value) {
                $attributes[$attribute] = $value;
            }
        }
    }
    $return = [];
    foreach ($attributes as $attribute => $value) {
        $return[] = $attribute.'="'.htmlspecialchars($value).'"';
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
    $choiceAttributes = $getAttributes('choice_attributes', ['class' => trim(($options['multiple'] ? 'checkbox' : 'radio').' '.$this->getWarningClass())]);

    $choiceElements = [];
    $preferredElements = [];
    foreach ($options['choices'] as $label => $value) {
        $elementAttributes = [
            'id' => $this->getFieldId(),
            'name' => $this->getFieldName(),
            'type' => 'radio',
        ];

        if ($options['multiple']) {
            $elementAttributes['name'] .= '[]';
            $elementAttributes['type'] = 'checkbox';
        }

        $elementAttributes['value'] = $value;
        if (in_array((string) $value, $this->getValue(), true)) {
            $elementAttributes['checked'] = 'checked';
        }

        $elementAttributes = $this->getAttributeElements($elementAttributes, ['autofocus', 'disabled', 'required']);

        $element = sprintf('<div %s><label><input %s /><i class="form-helper"></i>%s</label></div>', implode(' ', $choiceAttributes), implode(' ', $elementAttributes), $this->getLabelStyle($label));

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
    $elementAttributes = $this->getAttributeElements($elementAttributes, ['autocomplete', 'disabled', 'pattern', 'readonly', 'required', 'size']);

    $choiceElements = [];
    $preferredElements = [];
    if (count($options['group_choices'])) {
        $choiceOptGroups = [];
        $preferredOptGroups = [];
        foreach ($options['group_choices'] as $optGroupLabel => $choices) {
            foreach ($choices as $label => $value) {
                $selected = in_array($value, $this->getValue(), true) ? ' selected="selected"' : '';
                $option = sprintf('<option value="%s"%s>%s</option>', $value, $selected, $this->getLabelStyle($label));

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
            $selected = in_array($value, $this->getValue(), true) ? ' selected="selected"' : '';
            $option = sprintf('<option value="%s"%s>%s</option>', $value, $selected, $this->getLabelStyle($label));

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
