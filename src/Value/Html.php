<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;

use function Redaxo\Core\View\escape;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('html')]
class Html extends AbstractValue
{
    public function enterObject()
    {
        if (!$this->needsOutput()) {
            return;
        }

        $html = $this->getElement('html');
        $label = $this->getElement('label');

        // BC
        if ('' == $html) {
            $html = $label;
        }

        $this->params['form_output'][$this->getId()] = $html;
    }

    public function getDescription(): string
    {
        return escape('html|name|label|<div class="block"></div>');
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'html',
            'values' => [
                'name' => ['type' => 'name',   'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_label')],
                'html' => ['type' => 'textarea',    'attributes' => ['class' => 'form-control rex-code'], 'label' => I18n::msg('yform_values_html_HTML')],
            ],
            'description' => I18n::msg('yform_values_html_description'),
            'db_type' => ['none'],
            'multi_edit' => 'always',
            'is_searchable' => false,
            'is_hiddeninlist' => true,
        ];
    }
}
