<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Core;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;

use function Redaxo\Core\View\escape;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('csrf')]
class Csrf extends AbstractValue
{
    public function preValidateAction(): void
    {
        if ($this->params['csrf_protection']) {
            $tokenid = 'yform_' . $this->params['form_name'];

            $value = (string) $this->getValue();

            if ($this->needsOutput() && $this->params['send']) {
                if ($value != CsrfToken::factory($tokenid)->getValue()) {
                    $this->params['warning'][$this->getId()] = $this->params['error_class'];
                    $error_message = $this->getElement('message');
                    if ('' == $error_message) {
                        $error_message = $this->params['csrf_protection_error_message'];
                    }
                    if (Core::isBackend()) {
                        $this->params['warning_messages'][$this->getId()] = I18n::msg('csrf_token_invalid');
                    } else {
                        $this->params['warning_messages'][$this->getId()] = $error_message;
                    }
                }
            }

            $this->setValue(CsrfToken::factory($tokenid)->getValue());

            if ($this->needsOutput()) {
                $this->params['form_output'][$this->getId()] = $this->parse('hidden');
            }
        }
    }

    public function getDescription(): string
    {
        return escape('csrf|name|label|message');
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'csrf',
            'values' => [
                'name' => ['type' => 'name', 'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_label')],
                'message' => ['type' => 'text', 'label' => I18n::msg('yform_validate_defaults_message')],
            ],
            'description' => I18n::msg('yform_values_csrf_description'),
            'db_type' => ['none'],
            'multi_edit' => 'always',
            'is_searchable' => false,
            'is_hiddeninlist' => true,
            'hidden' => true,
            'formbuilder' => false,
            'manager' => false,
        ];
    }
}
