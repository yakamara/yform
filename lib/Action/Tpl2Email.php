<?php

namespace Yakamara\YForm\Action;

use rex;
use rex_path;
use Yakamara\YForm\Email\Template;

use function is_array;

use const FILTER_VALIDATE_EMAIL;

class Tpl2Email extends AbstractAction
{
    public function executeAction(): void
    {
        $template_name = $this->getElement(2);
        if ($etpl = Template::getTemplate($template_name)) {
            $email_to = rex::getErrorEmail();

            if (filter_var($this->getElement(3), FILTER_VALIDATE_EMAIL)) {
                $email_to = $this->getElement(3);
            } else {
                foreach ($this->params['value_pool']['email'] as $key => $value) {
                    if ($this->getElement(3) == $key) {
                        $email_to = $value;
                        break;
                    }
                }
            }

            $email_to_name = $this->getElement(4);
            $warning_message = $this->getElement(5);

            if ($this->params['debug']) {
                dump($etpl);
            }

            $etpl = Template::replaceVars($etpl, $this->params['value_pool']['email']);

            $etpl['mail_to'] = $email_to;
            $etpl['mail_to_name'] = $email_to_name;

            if ('' != $etpl['attachments']) {
                $f = explode(',', $etpl['attachments']);
                $etpl['attachments'] = [];
                foreach ($f as $v) {
                    $etpl['attachments'][] = ['name' => $v, 'path' => rex_path::media($v)];
                }
            } else {
                $etpl['attachments'] = [];
            }

            if (isset($this->params['value_pool']['email_attachments']) && is_array($this->params['value_pool']['email_attachments'])) {
                foreach ($this->params['value_pool']['email_attachments'] as $v) {
                    $etpl['attachments'][] = ['name' => $v[0], 'path' => $v[1]];
                }
            }

            if ($this->params['debug']) {
                dump($etpl);
            }

            if (!Template::sendMail($etpl, $template_name)) {
                if ($this->params['debug']) {
                    dump('email could not be sent');
                }
                if ('' != $warning_message) {
                    $this->params['output'] .= $warning_message;
                }
                return;
            }
            if ($this->params['debug']) {
                dump('email sent');
            }
            return;
        }
        if ($this->params['debug']) {
            dump('Template: "' . rex_escape($template_name) . '" not found');
        }
    }

    public function getDescription(): string
    {
        return 'action|tpl2email|emailtemplate|[email@domain.de/email_label]|[email_name]|[Fehlermeldung wenn Versand fehlgeschlagen ist/html]';
    }
}
