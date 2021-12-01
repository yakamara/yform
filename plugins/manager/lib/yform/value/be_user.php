<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_be_user extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $value = $this->getValue() ?? '';
        $showValue = $this->getValue();

        // translate:yform_always=0,translate:yform_onlyifempty=1,translate:yform_never=2
        $only_empty = $this->getElement('only_empty');
        $user = rex::getUser();
        $user_login = '';
        if ($user) {
            $user_login = $user->getLogin();
        }
        switch ($only_empty) {
            case '0':
                // always change. update
                $value = $user_login;
                if ('' != $showValue) {
                    $showValue = rex_i18n::msg('yform_is').': '.$showValue."\n";
                }
                $showValue .= rex_i18n::msg('yform_will_set_to').': '.$value;
                break;
            case '1':
                // if empty / bei create
                if ('' == $showValue) {
                    $value = $user_login;
                    $showValue = rex_i18n::msg('yform_will_set_to').': '.$value;
                }
                break;
            default:
                // never
                $showValue = $value;
        }

        $this->setValue($value);

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.be_user-view.tpl.php', 'value.view.tpl.php'], ['showValue' => $showValue]);
            } elseif (1 == $this->getElement('show_value') && '' != $this->getValue()) {
                $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php', ['showValue' => $showValue]);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDB()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'be_user',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',   'label' => rex_i18n::msg('yform_values_defaults_label')],
                'only_empty' => ['type' => 'choice',  'label' => rex_i18n::msg('yform_values_datestamp_only_empty'), 'default' => '0', 'choices' => 'translate:yform_always=0,translate:yform_onlyifempty=1,translate:yform_never=2'],
                'show_value' => ['type' => 'checkbox',  'label' => rex_i18n::msg('yform_values_defaults_showvalue'), 'default' => '0', 'options' => '0,1'],
            ],
            'description' => rex_i18n::msg('yform_values_be_user_description'),
            'formbuilder' => false,
            'db_type' => ['varchar(191)'],
        ];
    }

    public static function getListValue($params)
    {
        if ('' == $params['value']) {
            return '-';
        }

        return $params['value'];
    }
}
