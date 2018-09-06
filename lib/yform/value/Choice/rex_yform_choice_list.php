<?php

class rex_yform_choice_list
{
    public $choices = [];

    private $options;

    private $choicesByValues = [];

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function createListFromStringArray($choices)
    {
        // Sicherstellen, dass das Array Label => Value enthaelt
        // ist bei normaler kommaseparierte Schreibweise vertauscht
        $choices = array_flip($choices);

        foreach ($choices as $label => $value) {
            $label = rex_i18n::translate($label);
            $this->choices[trim($label)] = trim($value);
            $this->choicesByValues[trim($value)] = trim($label);
        }
    }

    public function createListFromJson($choices)
    {
        $choices = json_decode(trim($choices), true);

        foreach ($choices as $label => $value) {
            if (!is_array($value)) {
                $label = rex_i18n::translate($label);
                $this->choices[trim($label)] = trim($value);
                $this->choicesByValues[trim($value)] = trim($label);
                continue;
            }
            // Im Template werden im `select` optgroup erstellt
            foreach ($value as $nestedLabel => $nestedValue) {
                $nestedLabel = rex_i18n::translate($nestedLabel);
                $this->choices[trim($label)][trim($nestedLabel)] = trim($nestedValue);
                $this->choicesByValues[trim($nestedValue)] = trim($nestedLabel);
            }
        }
    }

    public function createListFromSqlArray($choices)
    {
        foreach ($choices as $choice) {
            $value = isset($choice['value']) ? $choice['value'] : $choice['id'];
            $label = isset($choice['label']) ? $choice['label'] : $choice['name'];
            $label = rex_i18n::translate($label);

            // Im Template werden im `select` optgroup erstellt
            if ($this->options['group_by'] && isset($choice[$this->options['group_by']])) {
                $this->choices[$choice[$this->options['group_by']]][trim($label)] = trim($value);
                $this->choicesByValues[trim($value)] = trim($label);
                continue;
            }
            $this->choices[trim($label)] = trim($value);
            $this->choicesByValues[trim($value)] = trim($label);
        }
    }

    public function createView(array $requiredAttributes = [])
    {
        $otherViews = [];
        $preferredViews = [];
        $choices = $this->getChoices();

        $preferredChoices = $this->options['preferred_choices'];
        $choiceAttributes = $this->options['choice_attributes'];

        if (!is_callable($preferredChoices) && !empty($preferredChoices)) {
            $preferredChoices = function ($choice) use ($preferredChoices) {
                return in_array($choice, $preferredChoices, true);
            };
        }

        foreach ($choices as $label => $value) {
            if (is_array($value)) {
                $otherGroupChoices = [];
                $preferredGroupChoices = [];
                foreach ($value as $nestedLabel => $nestedValue) {
                    $view = new rex_yform_choice_view($nestedValue, $nestedLabel, $choiceAttributes, $requiredAttributes);

                    if ($preferredChoices && call_user_func($preferredChoices, $nestedValue, $nestedLabel)) {
                        $preferredGroupChoices[] = $view;
                    } else {
                        $otherGroupChoices[] = $view;
                    }
                }

                if (count($preferredGroupChoices)) {
                    $preferredViews[] = new rex_yform_choice_group_view($label, $preferredGroupChoices);
                }
                if (count($otherGroupChoices)) {
                    $otherViews[] = new rex_yform_choice_group_view($label, $otherGroupChoices);
                }

                continue;
            }

            $view = new rex_yform_choice_view($value, $label, $choiceAttributes, $requiredAttributes);

            if ($preferredChoices && call_user_func($preferredChoices, $value, $label)) {
                $preferredViews[] = $view;
            } else {
                $otherViews[] = $view;
            }
        }

        foreach ($preferredViews as $index => $view) {
            if ($view instanceof rex_yform_choice_group_view && 0 === count($view->getChoices())) {
                unset($preferredViews[$index]);
            }
        }
        foreach ($otherViews as $index => $view) {
            if ($view instanceof rex_yform_choice_group_view && 0 === count($view->getChoices())) {
                unset($otherViews[$index]);
            }
        }

        return new rex_yform_choice_list_view($otherViews, $preferredViews);
    }

    public function getDefaultValues(array $defaultChoices = [])
    {
        foreach ($defaultChoices as $index => $defaultChoice) {
            if (!isset($this->choicesByValues[trim($defaultChoice)])) {
                unset($defaultChoices[$index]);
            }
        }
        return $defaultChoices;
    }

    public function getProofedValues(array $values = [])
    {
        $proofed = [];
        foreach ($values as $index => $value) {
            if (isset($this->choicesByValues[trim($value)])) {
                $proofed[$value] = $value;
            }
        }
        return $proofed;
    }

    public function getCompleteListForEmail(array $values = [])
    {
        $list = [];
        foreach ($this->choicesByValues as $value => $label) {
            $prefix = '[ ]';
            if (in_array($value, $values)) {
                $prefix = '[⨉]';
            }
            if (isset($this->choices[$label]) && is_array($this->choices[$label])) {
                $prefix = '-- ';
            }
            $list[$value] = sprintf('%s %s', $prefix, $label);
        }
        return $list;
    }

    public function getSelectedListForEmail(array $values = [])
    {
        $proofed = [];
        foreach ($values as $index => $value) {
            if (isset($this->choicesByValues[trim($value)])) {
                $proofed[$value] = $this->choicesByValues[trim($value)];
            }
        }
        return $proofed;
    }

    public function getChoices()
    {
        return $this->choices;
    }

    public function getChoicesByValues()
    {
        return $this->choicesByValues;
    }

    public function getPlaceholder()
    {
        return $this->options['placeholder'];
    }

    public function isExpanded()
    {
        return $this->options['expanded'];
    }

    public function isMultiple()
    {
        return $this->options['multiple'];
    }
}
