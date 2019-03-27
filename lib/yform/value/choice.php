<?php
/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_choice extends rex_yform_value_abstract
{
    public static $yform_list_values = [];

    public function enterObject()
    {
        $choiceList = self::createChoiceList([
            'choice_attributes' => $this->getElement('choice_attributes'),
            'choices' => $this->getElement('choices'),
            'expanded' => $this->getElement('expanded'),
            'group_by' => $this->getElement('group_by'),
            'multiple' => $this->getElement('multiple'),
            'placeholder' => $this->getElement('placeholder'),
            'preferred_choices' => $this->getElement('preferred_choices'),
        ]);

        if (null === $this->getValue()) {
            $this->setValue([]);
        } elseif (!is_array($this->getValue())) {
            $this->setValue(explode(',', $this->getValue()));
        }

        $values = $this->getValue();

        if (!$values) {
            if (in_array($this->getElement('default'), $choiceList->getChoices(), true)) {
                $defaultChoices = [$this->getElement('default')];
            } else {
                $defaultChoices = explode(',', $this->getElement('default'));
            }
            if (!$choiceList->isMultiple() && count($defaultChoices) >= 2) {
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
            if ($choiceList->isExpanded()) {
                if ($this->getElement('attributes') !== false) {
                    $elementAttributes = $this->getAttributes('attributes', $elementAttributes);
                }

                $choiceAttributes = [
                    'id' => $this->getFieldId(),
                    'name' => $this->getFieldName(),
                    'type' => 'radio',
                ];
                if ($choiceList->isMultiple()) {
                    $choiceAttributes['name'] .= '[]';
                    $choiceAttributes['type'] = 'checkbox';
                }
            } else {
                $elementAttributes['id'] = $this->getFieldId();
                $elementAttributes['name'] = $this->getFieldName();

                if ($choiceList->isMultiple()) {
                    $elementAttributes['name'] .= '[]';
                    $elementAttributes['multiple'] = 'multiple';
                    $elementAttributes['size'] = count($choiceList->getChoices());
                }
                if ($this->getElement('attributes') !== false) {
                    $elementAttributes = $this->getAttributes('attributes', $elementAttributes, ['autocomplete', 'disabled', 'pattern', 'readonly', 'required', 'size']);
                }
            }

            $choiceListView = $choiceList->createView($choiceAttributes);

            $template = $choiceList->isExpanded() ? 'value.choice.check.tpl.php' : 'value.choice.select.tpl.php';
            $html = $this->parse($template, compact('choiceList', 'choiceListView', 'elementAttributes', 'groupAttributes'));
            $html = trim(preg_replace(['/\s{2,}/', '/>\s+/', '/\s+</'], [' ', '>', '<'], $html));
            $this->params['form_output'][$this->getId()] = $html;
        }

        $this->setValue(implode(',', $proofedValues));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['email'][$this->getName().'_LABELS'] = implode(', ', $choiceList->getSelectedListForEmail($values));
        $this->params['value_pool']['email'][$this->getName().'_LIST'] = implode("\n", $choiceList->getCompleteListForEmail($values));

        if ($this->saveInDb()) {
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
            'db_type' => ['text', 'int'],
            'famous' => true,
        ];
    }

    public static function getListValue($params)
    {
        $listValues = self::getListValues($params);
        $return = [];
        foreach (explode(',', $params['value']) as $value) {
            if (isset($listValues[$value])) {
                $return[] = rex_i18n::translate($listValues[$value]);
            }
        }

        return implode('<br />', $return);
    }

    public static function getListValues($params)
    {
        $fieldName = $params['field'];
        if (!isset(self::$yform_list_values[$fieldName])) {
            $field = $params['params']['field'];

            $choiceList = self::createChoiceList([
                'choice_attributes' => (isset($field['choice_attributes'])) ? $field['choice_attributes'] : '',
                'choices' => (isset($field['choices'])) ? $field['choices'] : [],
                'expanded' => (isset($field['expanded'])) ? $field['expanded'] : '',
                'group_by' => (isset($field['group_by'])) ? $field['group_by'] : '',
                'multiple' => (isset($field['multiple'])) ? $field['multiple'] : false,
                'placeholder' => (isset($field['placeholder'])) ? $field['placeholder'] : '',
                'preferred_choices' => (isset($field['preferred_choices'])) ? $field['preferred_choices'] : [],
            ]);

            $choices = $choiceList->getChoicesByValues();
            foreach ($choices as $value => $label) {
                self::$yform_list_values[$fieldName][$value] = $label;
            }
        }
        return self::$yform_list_values[$fieldName];
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

    public static function getSearchField($params)
    {
        $choiceList = self::createChoiceList([
            'choice_attributes' => (isset($params['field']['choice_attributes'])) ? $params['field']['choice_attributes'] : '',
            'choices' => (isset($params['field']['choices'])) ? $params['field']['choices'] : [],
            'expanded' => (isset($params['field']['expanded'])) ? $params['field']['expanded'] : '',
            'group_by' => (isset($params['field']['group_by'])) ? $params['field']['group_by'] : '',
            'multiple' => (isset($params['field']['multiple'])) ? $params['field']['multiple'] : false,
            'placeholder' => (isset($params['field']['placeholder'])) ? $params['field']['placeholder'] : '',
            'preferred_choices' => (isset($params['field']['preferred_choices'])) ? $params['field']['preferred_choices'] : [],
        ]);

        $choices = [];
        $choices['(empty)'] = '(empty)';
        $choices['!(empty)'] = '!(empty)';

        $choices += $choiceList->getChoicesByValues();

        if (isset($choices[''])) {
            unset($choices['']);
        }

        $params['searchForm']->setValueField('choice', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'choices' => $choices,
            'multiple' => 1,
            'notice' => rex_i18n::msg('yform_search_defaults_select_notice'),
        ]
        );
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();

        $field = $params['field']->getName();

        $self = new self();
        $values = $self->getArrayFromString($params['value']);

        $multiple = $params['field']->getElement('multiple') == 1;

        $where = [];
        foreach ($values as $value) {
            switch ($value) {
                case '(empty)':
                    $where[] = ' '.$sql->escapeIdentifier($field).' = ""';
                    break;
                case '!(empty)':
                    $where[] = ' '.$sql->escapeIdentifier($field).' != ""';
                    break;
                default:
                    if ($multiple) {
                        $where[] = ' ( FIND_IN_SET( '.$sql->escape($value).', '.$sql->escapeIdentifier($field).') )';
                    } else {
                        $where[] = ' ( '.$sql->escape($value).' = '.$sql->escapeIdentifier($field).' )';
                    }

                    break;
            }
        }

        if (count($where) > 0) {
            return ' ( '.implode(' or ', $where).' )';
        }
    }

    private static function createChoiceList($elements)
    {
        $self = new self();

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

        if ($elements['expanded'] == '1') {
            $options['expanded'] = true;
        }
        if ($elements['multiple'] == '1') {
            $options['multiple'] = true;
        }
        if ($elements['group_by'] !== false) {
            $options['group_by'] = $elements['group_by'];
        }
        if ($elements['preferred_choices'] !== false) {
            $options['preferred_choices'] = $self->getArrayFromString($elements['preferred_choices']);
        }
        if ($elements['placeholder'] !== false && trim($elements['placeholder']) !== '') {
            $options['placeholder'] = $elements['placeholder'];
        }
        if ($elements['choice_attributes'] !== false) {
            $options['choice_attributes'] = $elements['choice_attributes'];
        }
        $choicesElement = $elements['choices'];

        $choiceList = new rex_yform_choice_list($options);

        if (is_string($choicesElement) && rex_sql::getQueryType($choicesElement) == 'SELECT') {
            $sql = rex_sql::factory();
            $sql->setDebug($self->getParam('debug'));
            $choiceList->createListFromSqlArray(
                $sql->getArray($choicesElement)
            );
        } elseif (is_string($choicesElement) && strlen(trim($choicesElement)) > 0 && substr(trim($choicesElement), 0, 1) == '{') {
            $choiceList->createListFromJson($choicesElement);
        } else {
            $choiceList->createListFromStringArray(
                $self->getArrayFromString($choicesElement)
            );
        }
        return $choiceList;
    }
}
