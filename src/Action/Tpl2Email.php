<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\Attribute\AsAction;
use Redaxo\Core\Core;
use Redaxo\Core\Filesystem\Path;
use Yakamara\YForm\Email\Template;

use function Redaxo\Core\View\escape;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsAction('tpl2email')]
class Tpl2Email extends AbstractAction
{
    public function executeAction(): void
    {
        $template_name = $this->getElement(2);
        if ($etpl = Template::getTemplate($template_name)) {
            $email_to = Core::getErrorEmail();

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

            // BC
            if ('' == $this->getElement(3) && filter_var($this->getElement(4), FILTER_VALIDATE_EMAIL)) {
                $email_to = $this->getElement(4);
                $email_to_name = $this->getElement(5);
                $warning_message = $this->getElement(6);
            }
            // End BC

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
                    $etpl['attachments'][] = ['name' => $v, 'path' => Path::media($v)];
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
            dump('Template: "' . escape($template_name) . '" not found');
        }

    }

    public function getDescription(): string
    {
        return 'action|tpl2email|emailtemplate|[email@domain.de/email_label]|[email_name]|[Fehlermeldung wenn Versand fehlgeschlagen ist/html]';
    }
}
