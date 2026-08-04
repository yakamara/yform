<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use InvalidArgumentException;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Choice\ChoiceGroupView;
use Yakamara\YForm\Choice\ChoiceList;
use Yakamara\YForm\Choice\ChoiceView;
use Yakamara\YForm\Manager\Query;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('choice')]
class Choice extends AbstractValue
{
    public static $yform_list_values = [];

    public function enterObject()
    {
        $choiceList = self::createChoiceList([
            'choice_attributes' => $this->getElement('choice_attributes'),
            'choice_label' => $this->getElement('choice_label'),
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
                throw new InvalidArgumentException('Expecting one default value for ' . $this->getFieldName() . ', but ' . count($defaultChoices) . ' given!');
            }
            $this->setValue($choiceList->getDefaultValues($defaultChoices));
        }

        $proofedValues = $choiceList->getProofedValues($values);

        if ($this->needsOutput() && $this->isViewable()) {
            $groupAttributes = [];
            if (false !== $this->getElement('group_attributes')) {
                $groupAttributes = $this->getAttributes('group_attributes', $groupAttributes);
            }

            $choiceAttributes = [];
            $elementAttributes = [];
            if ($choiceList->isExpanded()) {
                if (false !== $this->getElement('attributes')) {
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
                $elementAttributes = $this->getAttributes('attributes', $elementAttributes, ['autocomplete', 'disabled', 'pattern', 'readonly', 'required', 'size']);
            }

            $choiceListView = $choiceList->createView($choiceAttributes);

            $template = $choiceList->isExpanded() ? 'choice.check' : 'choice.select';

            if (!$this->isEditable()) {
                $template = str_replace('choice', 'choice-view', $template);
                $getChoices = static function ($choices, $options) use (&$getChoices) {
                    foreach ($choices as $choice) {
                        if ($choice instanceof ChoiceGroupView) {
                            /** @var ChoiceGroupView $choice */
                            $options = $getChoices($choice->choices, $options);
                        } else {
                            /* @var ChoiceView $choice */
                            $options[$choice->getValue()] = $choice->getLabel();
                        }
                    }
                    return $options;
                };
                $options = $getChoices($choiceListView->choices, []);
                $html = $this->parse([$template, 'view'], compact('options', 'choiceList', 'choiceListView', 'elementAttributes', 'groupAttributes'));
            } else {
                $html = $this->parse($template, compact('choiceList', 'choiceListView', 'elementAttributes', 'groupAttributes'));
            }

            $html = trim(preg_replace(['/\s{2,}/', '/>\s+/', '/\s+</'], [' ', '>', '<'], $html));
            $this->params['form_output'][$this->getId()] = $html;
        }

        $this->setValue(implode(',', $proofedValues));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['email'][$this->getName() . '_LABELS'] = implode(', ', $choiceList->getSelectedListForEmail($values));
        $this->params['value_pool']['email'][$this->getName() . '_LIST'] = implode("\n", $choiceList->getCompleteListForEmail($values));

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'choice|name|label|choices|[expanded type: boolean; default: 0, 0,1]|[multiple type: boolean; default: 0, 0,1]|[default]|[group_by]|[preferred_choices]|[placeholder]|[group_attributes]|[attributes]|[choice_attributes]|[notice]|[no_db]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'choice',
            'values' => [
                'name' => ['type' => 'name', 'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_label')],
                'choices' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_choices'), 'notice' => I18n::msg('yform_values_choice_choices_notice') . I18n::rawMsg('yform_values_choice_choices_table')],
                'expanded' => ['type' => 'boolean', 'label' => I18n::msg('yform_values_choice_expanded'), 'notice' => I18n::msg('yform_values_choice_expanded_notice')],
                'multiple' => ['type' => 'boolean', 'label' => I18n::msg('yform_values_choice_multiple'), 'notice' => I18n::msg('yform_values_choice_multiple_notice') . I18n::rawMsg('yform_values_choice_expanded_multiple_table')],
                'default' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_default'), 'notice' => I18n::msg('yform_values_choice_default_notice')],
                'group_by' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_group_by'), 'notice' => I18n::msg('yform_values_choice_group_by_notice')],
                'preferred_choices' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_preferred_choices'), 'notice' => I18n::msg('yform_values_choice_preferred_choices_notice')],
                'placeholder' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_placeholder'), 'notice' => I18n::msg('yform_values_choice_placeholder_notice')],
                'group_attributes' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_group_attributes'), 'notice' => I18n::msg('yform_values_choice_group_attributes_notice')],
                'attributes' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_attributes'), 'notice' => I18n::msg('yform_values_choice_attributes_notice')],
                'choice_attributes' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_choice_attributes'), 'notice' => I18n::msg('yform_values_choice_choice_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_notice')],
                'no_db' => ['type' => 'no_db', 'label' => I18n::msg('yform_values_defaults_table'), 'default' => 0],
                'choice_label' => ['type' => 'text', 'label' => I18n::msg('yform_values_choice_choice_label'), 'notice' => I18n::msg('yform_values_choice_choice_label_notice')],
            ],
            'description' => I18n::msg('yform_values_choice_description'),
            'db_type' => ['text', 'int', 'int(10) unsigned', 'tinyint', 'tinyint(1)', 'varchar(191)'],
            'famous' => true,
        ];
    }

    public static function getListValue($params)
    {
        $listValues = self::getListValues($params);
        $return = [];
        foreach (explode(',', $params['value']) as $value) {
            if (isset($listValues[$value])) {
                $return[] = I18n::translate($listValues[$value]);
            }
        }

        return implode('<br />', $return);
    }

    public static function getListValues($params)
    {
        $fieldName = $params['field'];
        $field = $params['params']['field'];
        $tableName = $field['table_name'];

        if (!isset(self::$yform_list_values[$tableName][$fieldName])) {
            $choiceList = self::createChoiceList([
                'choice_attributes' => (isset($field['choice_attributes'])) ? $field['choice_attributes'] : '',
                'choice_label' => (isset($field['choice_label'])) ? $field['choice_label'] : '',
                'choices' => (isset($field['choices'])) ? $field['choices'] : [],
                'expanded' => (isset($field['expanded'])) ? $field['expanded'] : '',
                'group_by' => (isset($field['group_by'])) ? $field['group_by'] : '',
                'multiple' => (isset($field['multiple'])) ? $field['multiple'] : false,
                'placeholder' => (isset($field['placeholder'])) ? $field['placeholder'] : '',
                'preferred_choices' => (isset($field['preferred_choices'])) ? $field['preferred_choices'] : [],
            ]);

            $choices = $choiceList->getChoicesByValues();
            foreach ($choices as $value => $label) {
                self::$yform_list_values[$tableName][$fieldName][$value] = $label;
            }
        }
        return self::$yform_list_values[$tableName][$fieldName] ?? '';
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
            if ($element = $this->getElement($attribute)) {
                $attributes[$attribute] = $element;
            }
        }

        return $attributes;
    }

    public static function getSearchField($params)
    {
        $choiceList = self::createChoiceList([
            'choice_attributes' => (isset($params['field']['choice_attributes'])) ? $params['field']['choice_attributes'] : '',
            'choice_label' => (isset($params['field']['choice_label'])) ? $params['field']['choice_label'] : '',
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

        $params['searchForm']->setValueField(
            'choice',
            [
                'name' => $params['field']->getName(),
                'label' => $params['field']->getLabel(),
                'choices' => $choices,
                'multiple' => 1,
                'notice' => I18n::msg('yform_search_defaults_select_notice'),
            ],
        );
    }

    public static function getSearchFilter($params)
    {
        $value = $params['value'];
        /** @var Query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();

        $self = new self();
        $values = $self->getArrayFromString($value);
        $multiple = 1 == $params['field']->getElement('multiple');

        foreach ($values as $value) {
            switch ($value) {
                case '(empty)':
                    // A NULL cell is empty too — REDAXO 6 makes the generated columns nullable.
                    $query->whereNested(static function (Query $query) use ($field) {
                        $query->where($field, '')->whereNull($field);
                    }, 'OR');
                    break;
                case '!(empty)':
                    $query->whereNested(static function (Query $query) use ($field) {
                        $query->where($field, '', '<>')->whereNotNull($field);
                    }, 'AND');
                    break;
                default:
                    if ($multiple) {
                        $query->whereListContains($field, $value);
                    } else {
                        $query->where($field, $value);
                    }
                    break;
            }
        }

        return $query;
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
            'choice_label' => [],
        ];

        if ('1' == $elements['expanded']) {
            $options['expanded'] = true;
        }
        if ('1' == $elements['multiple']) {
            $options['multiple'] = true;
        }
        if (false !== $elements['group_by']) {
            $options['group_by'] = $elements['group_by'];
        }
        if (false !== $elements['preferred_choices']) {
            $options['preferred_choices'] = $self->getArrayFromString($elements['preferred_choices']);
        }
        if (false !== $elements['placeholder'] && '' !== trim($elements['placeholder'])) {
            $options['placeholder'] = I18n::translate($elements['placeholder']);
        }
        if (false !== $elements['choice_attributes']) {
            $options['choice_attributes'] = $elements['choice_attributes'];
        }
        if (false !== $elements['choice_label']) {
            $options['choice_label'] = $elements['choice_label'];
        }
        $choicesElement = $elements['choices'];

        $choiceList = new ChoiceList($options);

        if (is_string($choicesElement) && 'SELECT' == Sql::getQueryType($choicesElement)) {
            $sql = Sql::factory();
            $sql->setDebug($self->getParam('debug'));
            $choiceList->createListFromSqlArray(
                $sql->getArray($choicesElement),
            );
        } elseif (is_string($choicesElement) && mb_strlen(trim($choicesElement)) > 0 && '{' == mb_substr(trim($choicesElement), 0, 1) && '{{' != mb_substr(trim($choicesElement), 0, 2)) {
            $choiceList->createListFromJson($choicesElement);
        } elseif (is_callable($choicesElement)) {
            $res = call_user_func($choicesElement);
            if (is_array($res)) {
                $choiceList->createListFromStringArray($res);
            } else {
                $choiceList->createListFromJson($res);
            }
        } else {
            $choiceList->createListFromStringArray(
                $self->getArrayFromString($choicesElement),
            );
        }
        return $choiceList;
    }
}
