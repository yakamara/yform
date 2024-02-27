<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_callback extends rex_yform_action_abstract
{
    public static $callback_actions = [
        'pre',
        'post',
        'normal',
    ];

    public function executeAction(): void
    {
        $this->action_callback();
    }

    public function preAction(): void
    {
        $this->action_callback('pre');
    }

    public function postAction(): void
    {
        $this->action_callback('post');
    }

    public function action_callback($currentFunction = 'normal')
    {
        if (!$this->getElement(2)) {
            return;
        }

        $userFunction = $this->getElement(3);

        if (!in_array($userFunction, self::$callback_actions, true)) {
            $userFunction = 'normal';
        }

        if ($currentFunction === $userFunction) {
            call_user_func($this->getElement(2), $this);
        }
    }

    public function getDescription(): string
    {
        return 'action|callback|mycallback / myclass::mycallback|pre/post/[normal]';
    }
}
