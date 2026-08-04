<?php

namespace Yakamara\YForm\Choice;

use Redaxo\Core\Translation\I18n;

use function Redaxo\Core\View\escape;

class ChoiceList
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
            $label = I18n::translate((string) $label, false);
            $this->choices[trim($label)] = trim($value);
            $this->choicesByValues[trim($value)] = trim($label);
        }
    }

    public function createListFromJson($choices)
    {
        $choices = json_decode(trim($choices), true);

        foreach ($choices as $label => $value) {
            if (!is_array($value)) {
                $label = I18n::translate((string) $label, false);
                $this->choices[trim($label)] = trim($value);
                $this->choicesByValues[trim($value)] = trim($label);
                continue;
            }
            // Im Template werden im `select` optgroup erstellt
            foreach ($value as $nestedLabel => $nestedValue) {
                $nestedLabel = I18n::translate((string) $nestedLabel, false);
                $this->choices[trim($label)][trim($nestedLabel)] = trim($nestedValue);
                $this->choicesByValues[trim($nestedValue)] = trim($nestedLabel);
            }
        }
    }

    public function createListFromSqlArray($choices)
    {
        foreach ($choices as $choice) {
            $value = $choice['value'] ?? $choice['id'];
            $label = $choice['label'] ?? $choice['name'];
            $label = I18n::translate((string) $label, false);

            // Im Template werden im `select` optgroup erstellt
            if (isset($this->options['group_by']) && $this->options['group_by'] && isset($choice[$this->options['group_by']])) {
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
        $choiceLabel = $this->options['choice_label'];

        if (!is_callable($preferredChoices) && !empty($preferredChoices)) {
            $preferredChoices = static function ($choice) use ($preferredChoices) {
                return in_array($choice, $preferredChoices, true);
            };
        }

        foreach ($choices as $label => $value) {
            if (is_array($value)) {
                $otherGroupChoices = [];
                $preferredGroupChoices = [];
                foreach ($value as $nestedLabel => $nestedValue) {
                    if (is_callable($choiceLabel)) {
                        $nestedLabel = call_user_func($choiceLabel, $nestedValue, $nestedLabel);
                    } else {
                        $nestedLabel = escape($nestedLabel);
                    }
                    $view = new ChoiceView($nestedValue, $nestedLabel, $choiceAttributes, $requiredAttributes);

                    if ($preferredChoices && call_user_func($preferredChoices, $nestedValue, $nestedLabel)) {
                        $preferredGroupChoices[] = $view;
                    } else {
                        $otherGroupChoices[] = $view;
                    }
                }

                if (count($preferredGroupChoices)) {
                    $preferredViews[] = new ChoiceGroupView($label, $preferredGroupChoices);
                }
                if (count($otherGroupChoices)) {
                    $otherViews[] = new ChoiceGroupView($label, $otherGroupChoices);
                }

                continue;
            }

            if (is_callable($choiceLabel)) {
                $label = call_user_func($choiceLabel, $value, $label);
            } else {
                $label = escape($label);
            }

            $view = new ChoiceView($value, $label, $choiceAttributes, $requiredAttributes);

            if ($preferredChoices && call_user_func($preferredChoices, $value, $label)) {
                $preferredViews[] = $view;
            } else {
                $otherViews[] = $view;
            }
        }

        foreach ($preferredViews as $index => $view) {
            if ($view instanceof ChoiceGroupView && 0 === count($view->getChoices())) {
                unset($preferredViews[$index]);
            }
        }
        foreach ($otherViews as $index => $view) {
            if ($view instanceof ChoiceGroupView && 0 === count($view->getChoices())) {
                unset($otherViews[$index]);
            }
        }

        return new ChoiceListView($otherViews, $preferredViews);
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
