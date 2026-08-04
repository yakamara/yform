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

#[AsValue('showvalue')]
class ShowValue extends AbstractValue
{
    public function enterObject()
    {
        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('showvalue');
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
    }

    public function getDescription(): string
    {
        return 'showvalue|name|label|defaultwert|notice';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'showvalue',
            'values' => [
                'name' => ['type' => 'name',    'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text',    'label' => I18n::msg('yform_values_text_default')],
                'notice' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_notice')],
            ],
            'description' => I18n::msg('yform_values_showvalue_description'),
            'db_type' => ['text', 'varchar(191)'],
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
