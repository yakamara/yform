<?php

use Psr\Log\LogLevel;

class rex_yform_action_logger extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        switch (strtolower($this->getElement(3))) {
            case 'error':
                $level = LogLevel::ERROR;
                break;
            case 'warning':
                $level = LogLevel::WARNING;
                break;
            case 'notice':
                $level = LogLevel::NOTICE;
                break;
            case 'info':
            default:
                $level = LogLevel::INFO;
                break;
        }

        $logInfo = $this->getElement(2) ?? 'No log text defined';

        foreach ($this->params['value_pool']['email'] as $search => $replace) {
            if (is_scalar($search) && is_scalar($replace)) {
                $logInfo = str_replace('{' . $search . '}', $replace, $logInfo);
            }
        }

        rex_logger::factory()->log($level, 'YForm Action: rex_logger: ' . $logInfo);
    }

    public function getDescription(): string
    {
        return 'action|logger|[Logtext]|[Loglevel: info, notice, warning, error]';
    }
}
