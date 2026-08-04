<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\Attribute\AsAction;
use Psr\Log\LogLevel;
use Redaxo\Core\Log\Logger as CoreLogger;

#[AsAction('logger')]
class Logger extends AbstractAction
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

        CoreLogger::factory()->log($level, 'YForm Action: CoreLogger: ' . $logInfo);
    }

    public function getDescription(): string
    {
        return 'action|logger|[Logtext]|[Loglevel: info, notice, warning, error]';
    }
}
