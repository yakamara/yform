<?php

/**
 * yform.
 *
 */



class rex_yform_action_attach extends rex_yform_action_abstract
{
    public function executeAction()
    {
        $fields = array_filter(explode(",", $this->getElement(2))); // specific fields
        $override = (bool) $this->getElement(3) ?? false; // will replace attached files
        $uploaded_files = &$this->params['value_pool']['files']; // form uploaded files
        $email_attachments = &$this->params['value_pool']['email_attachments']; // attached files

        if (count($fields)) {
            if ($override) {
                $email_attachments = [];
            }
            foreach ($uploaded_files as $file) {
                $email_attachments[] = $file;
            }
        } else {
            if ($override) {
                $email_attachments = [];
            }
            foreach ($fields as $field) {
                $email_attachments[] = $uploaded_files[$field];
            }
        }
    }

    public function getDescription()
    {
        return 'action|attach|opt:uploadfields(upload1,upload2,upload3)|opt:replace(0=default/1)';
    }
}
