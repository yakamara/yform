<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('textarea')]
class Textarea extends AbstractValue
{
    public function enterObject()
    {
        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('textarea');
        }

        if ($this->needsOutput() && $this->isViewable()) {
            $templateParams = [];
            if (!$this->isEditable()) {
                $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
                $attributes['readonly'] = 'readonly';
                $this->setElement('attributes', json_encode($attributes));
                $this->params['form_output'][$this->getId()] = $this->parse(['value.textarea-view.tpl.php', 'view', 'textarea'], $templateParams);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('textarea', $templateParams);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'textarea|name|label|default|[no_db]|[attributes]|notice';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'textarea',
            'values' => [
                'name' => ['type' => 'name',   'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'textarea',    'label' => I18n::msg('yform_values_textarea_default')],
                'no_db' => ['type' => 'no_db',   'label' => I18n::msg('yform_values_defaults_table'),  'default' => 0],
                'attributes' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_attributes'), 'notice' => I18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_notice')],
            ],
            'description' => I18n::msg('yform_values_textarea_description'),
            'db_type' => ['text', 'mediumtext'],
            'search' => true,
            'list_hidden' => false,
            'famous' => true,
        ];
    }

    public static function getSearchField($params)
    {
        Text::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return Text::getSearchFilter($params);
    }

    public static function getListValue($params)
    {
        return Text::getListValue($params);
    }
}
