<?php

namespace Yakamara\YForm\Action;

use function function_exists;
use function in_array;

class EncryptValue extends AbstractAction
{
    public function executeAction(): void
    {
        $f = $this->getElement(3); // the function
        if (!function_exists($f)) {
            $f = 'md5';
        }

        // Labels to get
        $l = explode(',', $this->getElement(2));

        // Label to save in
        $ls = @$this->getElement(4);
        if ('' == $ls) {
            $ls = $l[0];
        }
        if ('' == $ls) {
            return;
        }

        $k = '';
        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if (in_array($key, $l)) {
                $k .= $value;
            }
        }

        if ('' != $k) {
            $this->params['value_pool']['sql'][$ls] = $f($k);
            $this->params['value_pool']['email'][$ls] = $f($k);
        }
    }

    public function getDescription(): string
    {
        return 'action|encrypt|label[,label2,label3]|md5|[save_in_this_label]';
    }
}
