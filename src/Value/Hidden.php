<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\AbstractBase;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('hidden')]
class Hidden extends AbstractValue
{
    public function loadParams(&$params, $elements = [])
    {
        // Bypass the abstract's setLabel($this->getElement(2)) because for
        // `hidden`, element 2 is the VALUE — not a label. Coerce all property
        // assignments to string so the typed parent props don't TypeError when
        // callers pass non-string defaults (#1350).
        AbstractBase::loadParams($params, $elements);
        $this->setName((string) $this->getElement(1));
        $this->setLabel('');
        $this->type = (string) $this->getElement(0);
    }

    public function setValue($value)
    {
        if ('GET' == $this->getElement(3) && isset($_GET[$this->getElement(2)])) {
            $this->value = Request::get($this->getElement(2), 'string', '');
            $this->params['form_action_query_params'][$this->getElement(2)] = $this->value;
        } elseif ('REQUEST' == $this->getElement(3) && isset($_REQUEST[$this->getElement(2)])) {
            $this->value = Request::request($this->getElement(2), 'string', '');
        } elseif ('POST' == $this->getElement(3) && isset($_POST[$this->getElement(2)])) {
            $this->value = Request::post($this->getElement(2), 'string', '');
        } elseif ('SESSION' == $this->getElement(3) && null !== Request::session($this->getElement(2), 'string', null)) {
            $this->value = Request::session($this->getElement(2), 'string', '');
        } else {
            $this->value = $this->getElement(2);
        }
    }

    public function enterObject()
    {
        if ($this->needsOutput() && in_array($this->getElement(3), ['POST', 'REQUEST'])) {
            $this->params['form_output'][$this->getId()] = $this->parse('hidden', ['fieldName' => $this->getElement(1)]);
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb('4')) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'hidden|fieldname|value||[no_db]' . "\n" . 'hidden|fieldname|key|REQUEST/GET/POST/SESSION|[no_db]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'hidden',
            'values' => [
                'name' => ['type' => 'name', 'label' => I18n::msg('yform_values_defaults_name')],
                'value' => ['type' => 'text', 'label' => 'Default-Wert oder Request-/Session-Key'],
                'source' => ['type' => 'choice', 'label' => 'Quelle', 'choices' => '=,GET=GET,POST=POST,REQUEST=REQUEST,SESSION=SESSION', 'default' => ''],
                'no_db' => ['type' => 'no_db', 'label' => I18n::msg('yform_values_defaults_table'), 'default' => 0],
            ],
            'description' => 'Verstecktes Feld mit Default-Wert oder Wert aus GET/POST/REQUEST/SESSION.',
            'db_type' => ['varchar(191)', 'text'],
            'famous' => false,
        ];
    }
}
