<?php

class rex_yform_value_spamfilter extends rex_yform_value_abstract
{
    public function postValidateAction()
    {
        rex_login::startSession();

        $microtimestamp = rex_request::session('spamfilter');
        $formmicrotimestamp = rex_request($this->getFieldId()."_microtime");

        $log = [];
        
        if ($this->params['send'] == 1) {

            if(rex_request($this->getFieldId()) != "") {
                $this->params['warning'][$this->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$this->getId()] = $this->getElement(3);
                $log[] = "honeypot wurde ausgefüllt: ".rex_request($this->getFieldId());
            }

            if (($microtimestamp + 5) > microtime(true)) {
                $this->params['warning'][$this->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$this->getId()] = $this->getElement(3);
                $log[] = "microtime nicht eingehalten: $microtimestamp + 5 > ".microtime(true);
            }

            if (($formmicrotimestamp + 5) > microtime(true)) {
                $this->params['warning'][$this->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$this->getId()] = $this->getElement(3);
                $log[] = "microtime nicht eingehalten: $formmicrotimestamp +5 > ".microtime(true);
            }

        }

        if($this->getElement(4)) {
            rex_logger::logError(E_NOTICE, implode(",",$log), __FILE__, __LINE__);
        }

        rex_request::setSession('spamfilter', microtime(true));

    }

    public function enterObject()
    {
        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.spamfilter.tpl.php', []);
        }

    }

    public function getDescription()
    { 
        return 'spamfilter|name|Label|Fehlermeldung|Log(0/1)';
    }

}
