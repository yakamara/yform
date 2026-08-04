<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Manager\Query;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('checkbox')]
class Checkbox extends AbstractValue
{
    public function enterObject()
    {
        if (1 == $this->params['send'] && 1 != $this->getValue()) {
            $this->setValue(0);
        } elseif ('' != $this->getValue()) {
            $this->setValue((1 != $this->getValue()) ? '0' : '1');
        } else {
            $this->setValue($this->getElement('default'));
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
                $attributes['disabled'] = 'disabled';
                $this->setElement('attributes', json_encode($attributes));
                $this->params['form_output'][$this->getId()] = $this->parse(['value.checkbox-view.tpl.php', 'checkbox'], ['value' => $this->getValue()]);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('checkbox', ['value' => $this->getValue()]);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'checkbox|name|label|default clicked (0/1)|[no_db]|[notice]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'checkbox',
            'values' => [
                'name' => ['type' => 'name', 'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'checkbox', 'label' => I18n::msg('yform_values_checkbox_default'), 'default' => 0],
                'no_db' => ['type' => 'no_db', 'label' => I18n::msg('yform_values_defaults_table'), 'default' => 0],
                'attributes' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_attributes'), 'notice' => I18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_notice')],
                'output_values' => ['type' => 'text', 'label' => I18n::msg('yform_values_status_output_values'), 'notice' => I18n::msg('yform_values_status_output_values_notice')],
            ],
            'description' => I18n::msg('yform_values_checkbox_description'),
            'db_type' => ['tinyint(1)'],
            'famous' => true,
            'hooks' => [
                'preDefault' => static function (Field $field) {
                    return (1 == $field->getElement('default')) ? '1' : '0';
                },
            ],
        ];
    }

    public static function getSearchField($params)
    {
        $options = explode(',', $params['field']['output_values'] ?? '');
        if (2 != count($options)) {
            $options[0] = I18n::rawMsg('yform_values_not_checked');
            $options[1] = I18n::rawMsg('yform_values_checked');
        }
        $options[''] = '---';

        $params['searchForm']->setValueField('choice', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'choices' => $options,
        ]);
    }

    public static function getSearchFilter($params): Query
    {
        $value = $params['value'];
        /** @var Query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();

        return $query->where($field, $value);
    }

    public static function getListValue($params)
    {
        $values = explode(',', $params['params']['field']['output_values'] ?? '');
        if (2 != count($values)) {
            $values = [0, 1];
        }

        if (1 === $params['subject']) {
            return (string) $values[1];
        }
        if (0 === $params['subject']) {
            return (string) $values[0];
        }

        // Core's Formatter::custom() is typed `: string` — a NULL cell would be fatal.
        return (string) $params['subject'];
    }
}
