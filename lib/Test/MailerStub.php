<?php

declare(strict_types=1);

namespace Redaxo\YForm\Test;

use rex_extension;
use rex_extension_point;

use function count;
use function is_array;

/**
 * Intercepts YForm email sends via the YFORM_EMAIL_BEFORE_SEND extension
 * point, so tests can assert recipient / subject / body without spinning up
 * a real SMTP transport.
 *
 * Returning a payload with status=true short-circuits sendMail() before the
 * PHPMailer Send() call.
 *
 * @package redaxo\yform
 * @internal
 */
final class MailerStub
{
    /** @var list<array> */
    private array $sentMails = [];

    private bool $active = false;

    public function activate(): void
    {
        if ($this->active) {
            return;
        }
        $this->active = true;

        rex_extension::register('YFORM_EMAIL_BEFORE_SEND', function (rex_extension_point $ep) {
            $data = $ep->getSubject();
            if (!is_array($data)) {
                return $data;
            }
            $template = $data['template'] ?? null;
            if (is_array($template)) {
                $this->sentMails[] = $template;
            }
            $data['status'] = true;
            return $data;
        });
    }

    public function reset(): void
    {
        $this->sentMails = [];
    }

    /**
     * @return list<array>
     */
    public function getSentMails(): array
    {
        return $this->sentMails;
    }

    public function lastMail(): ?array
    {
        if (!$this->sentMails) {
            return null;
        }
        return $this->sentMails[array_key_last($this->sentMails)];
    }

    public function count(): int
    {
        return count($this->sentMails);
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
