<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_choice extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $options = [
            'choices' => [],
            'group_choices' => [],
            'preferred_choices' => [],
            'expanded' => false,
            'multiple' => false,
            'group_by' => null,
            'placeholder' => null,
        ];

        if ($this->getElement('expanded') == '1') {
            $options['expanded'] = true;
        }
        if ($this->getElement('multiple') == '1') {
            $options['multiple'] = true;
        }
        if ($this->getElement('group_by') !== false) {
            $options['group_by'] = $this->getElement('group_by');
        }
        if ($this->getElement('preferred_choices') !== false) {
            $options['preferred_choices'] = $this->getArrayFromString($this->getElement('preferred_choices'));
        }
        if ($this->getElement('placeholder') !== false) {
            $options['placeholder'] = $this->getElement('placeholder');
        }

        $choicesString = $this->getElement('choices');
        if (rex_sql::getQueryType($choicesString) == 'SELECT') {
            $sql = rex_sql::factory();
            $sql->setDebug($this->params['debug']);

            try {
                foreach ($sql->getArray($choicesString) as $result) {
                    $value = isset($result['value']) ? $result['value'] : $result['id'];
                    $label = isset($result['label']) ? $result['label'] : $result['name'];
                    $options['choices'][$label] = $value;

                    if (!$options['expanded'] && null !== $options['group_by']) {
                        // Im Template werden im `select` optgroup erstellt
                        if (isset($result[$options['group_by']])) {
                            $options['group_choices'][$result[$options['group_by']]][$label] = $value;
                        }
                    }
                }
            } catch (rex_sql_exception $e) {
                dump($e);
            }
        } else {
            if (is_string($choicesString) && trim($choicesString){0} == '{') {
                $choicesString = json_decode(trim($choicesString), true);
            }

            $flip = is_array($choicesString) ? false : true;
            $results = $this->getArrayFromString($choicesString);
            if ($flip) {
                // Sicherstellen, dass das Array Label => Value enthaelt
                // ist bei normaler kommaseparierte Schreibweise vertauscht
                $results = array_flip($results);
            }

            foreach ($results as $label => $value) {
                if (is_array($value)) {
                    foreach ($value as $optionLabel => $optionValue) {
                        // Im Template werden im `select` optgroup erstellt
                        $options['group_choices'][$label][$optionLabel] = $optionValue;
                        $options['choices'][trim($optionLabel)] = trim($optionValue);
                    }
                    continue;
                }
                $options['choices'][trim($label)] = trim($value);
            }
        }

        if (null === $this->getValue()) {
            $this->setValue([]);
        } elseif (!is_array($this->getValue())) {
            $this->setValue(explode(',', $this->getValue()));
        }

        $values = $this->getValue();

        if (!$values) {
            $defaultChoices = explode(',', $this->getElement('default'));

            if (!$options['multiple'] && count($defaultChoices) >= 2) {
                throw new InvalidArgumentException('Expecting one default value for '.$this->getFieldName().', but '.count($defaultChoices).' given!');
            }

            if ($defaultChoices) {
                $defaultValues = [];
                foreach ($defaultChoices as $defaultChoice) {
                    if (in_array($defaultChoice, $options['choices'])) {
                        $defaultValues[] = $defaultChoice;
                    }
                }
                $this->setValue($defaultValues);
            }
        }

        $proofedLabels = [];
        $proofedValues = [];
        foreach ($values as $value) {
            if (in_array($value, $options['choices'])) {
                $proofedLabels[$value] = array_search($value, $options['choices']);
                $proofedValues[$value] = $value;
            }
        }
        $proofedList = [];
        foreach ($options['choices'] as $label => $value) {
            $prefix = '[ ]';
            if (in_array($value, $values)) {
                $prefix = '[⨉]';
            }
            $proofedList[$value] = sprintf('%s %s', $prefix, $label);
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.choice.tpl.php', compact('options'));
        }

        $this->setValue(implode(',', $proofedValues));

        $this->params['value_pool']['email'][$this->getName()] = implode(', ', $proofedLabels);
        $this->params['value_pool']['email'][$this->getName().'_LIST'] = implode("\n", $proofedList);

        if ($this->getElement('no_db') != 1) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'choice|name|label|choices|[expanded type: boolean; default: false]|[multiple type: boolean; default: false]|[default]|[group_by]|[preferred_choices]|[group_attributes]|[choice_attributes]|[attributes]|[notice]|[no_db]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'choice',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'choices' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_choice_choices'), 'notice' => rex_i18n::msg('yform_values_choice_choices_notice').rex_i18n::rawMsg('yform_values_choice_choices_table')],
                'expanded' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_choice_expanded'), 'notice' => rex_i18n::msg('yform_values_choice_expanded_notice')],
                'multiple' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_choice_multiple'), 'notice' => rex_i18n::msg('yform_values_choice_multiple_notice').rex_i18n::rawMsg('yform_values_choice_expanded_multiple_table')],
                'default' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_choice_default'), 'notice' => rex_i18n::msg('yform_values_choice_default_notice')],
                'group_by' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_choice_group_by'), 'notice' => rex_i18n::msg('yform_values_choice_group_by_notice')],
                'preferred_choices' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_choice_preferred_choices'), 'notice' => rex_i18n::msg('yform_values_choice_preferred_choices_notice')],
                'placeholder' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_choice_placeholder'), 'notice' => rex_i18n::msg('yform_values_choice_placeholder_notice')],
                'group_attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_choice_group_attributes'), 'notice' => rex_i18n::msg('yform_values_choice_group_attributes_notice')],
                'attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_choice_attributes'), 'notice' => rex_i18n::msg('yform_values_choice_attributes_notice')],
                'choice_attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_choice_choice_attributes'), 'notice' => rex_i18n::msg('yform_values_choice_choice_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
            ],
            'description' => rex_i18n::msg('yform_values_choice_description'),
            'dbtype' => 'text',
            'famous' => true,
        ];
    }

    public static function getListValue($params)
    {
        $return = [];

        $new_select = new self();
        $choices = $new_select->getArrayFromString($params['params']['field']['choices']);

        foreach (explode(',', $params['value']) as $value) {
            if (in_array($value, $choices)) {
                $return[] = rex_i18n::translate(array_search($value, $choices));
            }
        }

        return implode('<br />', $return);
    }
}
