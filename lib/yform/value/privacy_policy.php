<?php

class rex_yform_value_privacy_policy extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if (1 == $this->params['send'] && 1 != $this->getValue()) {
            $this->setValue(0);
        } elseif ('' != $this->getValue()) {
            $this->setValue((1 != $this->getValue()) ? '0' : '1');
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
                $attributes['disabled'] = 'disabled';
                $this->setElement('attributes', json_encode($attributes));
                $this->params['form_output'][$this->getId()] = $this->parse(['value.checkbox-view.tpl.php', 'value.checkbox-privacy_policy.tpl.php'], ['value' => $this->getValue()]);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.checkbox-privacy_policy.tpl.php', ['value' => $this->getValue()]);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb() &&  $this->getValue() == 1) {
            $this->params['value_pool']['sql'][$this->getName()] = date("Y-m-d H:i:s");
        }
    }

    public function getDescription()
    {
        return 'privacy_policy|name|label|[no_db]|text|linktext|article_id';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'privacy_policy',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
                'attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'output_values' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_status_output_values'), 'notice' => rex_i18n::msg('yform_values_status_output_values_notice')],
                'text' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_privacy_policy_text')],
                'linktext' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_privacy_policy_linktext')],
                'article_id' => ['type' => 'be_link', 'label' => rex_i18n::msg('yform_values_privacy_policy_article_id')]
            ],
            'description' => rex_i18n::msg('yform_values_checkbox_description'),
            'db_type' => ['datetime'],
        ];
    }

    public static function getSearchField($params)
    {
        $v = 1;
        $w = 0;

        $options = [];
        $options[$v] = rex_i18n::rawMsg('yform_values_checked');
        $options[$w] = rex_i18n::rawMsg('yform_values_not_checked');
        $options[''] = '---';

        $params['searchForm']->setValueField('choice', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'choices' => $options,
        ]);
    }

    public static function getSearchFilter($params)
    {
        $value = $params['value'];
        /** @var rex_yform_manager_query $query */
        $query = $params['query'];
        $field = $params['field']->getName();

        return $query->where($field, $value);
    }

    public static function getListValue($params)
    {
        $values = $params['params']['field']['output_values'] ?? '0,1';
        $values = explode(',', $values);
        if (2 != count($values)) {
            $values = [0, 1];
        }

        if ('1' === $params['subject']) {
            return $values[1];
        }
        if ('0' === $params['subject']) {
            return $values[0];
        }

        return $params['subject'];
    }

    public function getViewValue()
    {
        return 'hihih';
    }
}
