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
            'choice_attributes' => [],
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
        if ($this->getElement('placeholder') !== false && trim($this->getElement('placeholder')) !== '') {
            $options['placeholder'] = $this->getElement('placeholder');
        }
        if ($this->getElement('choice_attributes') !== false) {
            $options['choice_attributes'] = $this->getElement('choice_attributes');
        }

        $choiceList = new rex_yform_choice_list($options);

        $choicesElement = $this->getElement('choices');
        if (rex_sql::getQueryType($choicesElement) == 'SELECT') {
            $sql = rex_sql::factory();
            $sql->setDebug($this->params['debug']);
            $choiceList->createListFromSqlArray(
                $sql->getArray($choicesElement)
            );
        } elseif (is_string($choicesElement) && trim($choicesElement){0} == '{') {
            $choiceList->createListFromJson($choicesElement);
        } else {
            $choiceList->createListFromStringArray(
                $this->getArrayFromString($choicesElement)
            );
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

            $this->setValue($choiceList->getDefaultValues($defaultChoices));
        }

        $proofedValues = $choiceList->getProofedValues($values);

        if ($this->needsOutput()) {
            $groupAttributes = [];
            if ($this->getElement('group_attributes') !== false) {
                $groupAttributes = $this->getAttributes('group_attributes', $groupAttributes);
            }

            $choiceAttributes = [];
            $elementAttributes = [];
            if ($options['expanded']) {
                if ($this->getElement('attributes') !== false) {
                    $elementAttributes = $this->getAttributes('attributes', $elementAttributes);
                }

                $choiceAttributes = [
                    'id' => $this->getFieldId(),
                    'name' => $this->getFieldName(),
                    'type' => 'radio',
                ];
                if ($options['multiple']) {
                    $choiceAttributes['name'] .= '[]';
                    $choiceAttributes['type'] = 'checkbox';
                }
            } else {
                $elementAttributes['id'] = $this->getFieldId();
                $elementAttributes['name'] = $this->getFieldName();

                if ($options['multiple']) {
                    $elementAttributes['name'] .= '[]';
                    $elementAttributes['multiple'] = 'multiple';
                    $elementAttributes['size'] = count($choiceList->getChoices());
                }
                if ($this->getElement('attributes') !== false) {
                    $elementAttributes = $this->getAttributes('attributes', $elementAttributes, ['autocomplete', 'disabled', 'pattern', 'readonly', 'required', 'size']);
                }
            }

            $choiceListView = $choiceList->createView($choiceAttributes);

            $this->params['form_output'][$this->getId()] = $this->parse('value.choice.tpl.php', compact('options', 'choiceListView', 'elementAttributes', 'groupAttributes'));
        }

        $this->setValue(implode(',', $proofedValues));

        $this->params['value_pool']['email'][$this->getName()] = implode(', ', $choiceList->getSelectedListForEmail($values));
        $this->params['value_pool']['email'][$this->getName().'_LIST'] = implode("\n", $choiceList->getCompleteListForEmail($values));

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

    public function getAttributes($element, array $attributes = [], array $directAttributes = [])
    {
        $additionalAttributes = $this->getElement($element);
        if ($additionalAttributes) {
            if (is_callable($additionalAttributes)) {
                $additionalAttributes = call_user_func($additionalAttributes, $attributes, $this->getValue());
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

        return $attributes;
    }
}
