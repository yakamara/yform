<?php

namespace Yakamara\YForm\Form\Handler;

use Closure;
use Redaxo\Core\Mailer\Mailer;
use RuntimeException;

use function implode;
use function is_string;

/**
 * Sends an e-mail. Subject and body are templates with `{field}` placeholders, or closures receiving the
 * submission. Without body, a plain list of all fields is sent.
 *
 * ```php
 * new SendMail(to: 'info@example.org', subject: 'Anfrage von {name}', replyTo: '{email}');
 * new SendMail(to: '{email}', subject: 'Danke!', body: fn (Submission $s) => $this->renderConfirmation($s), html: true);
 * ```
 */
final readonly class SendMail implements Handler
{
    /**
     * @param string|list<string> $to addresses or `{field}` placeholders
     * @param string|(Closure(Submission): string) $subject
     * @param string|(Closure(Submission): string)|null $body
     */
    public function __construct(
        public string|array $to,
        public string|Closure $subject,
        public string|Closure|null $body = null,
        public ?string $replyTo = null,
        public ?string $from = null,
        public ?string $fromName = null,
        public bool $html = false,
    ) {}

    public function handle(Submission $submission): void
    {
        $mailer = new Mailer(true);
        if (null !== $this->from) {
            $mailer->setFrom($submission->interpolate($this->from), (string) $this->fromName);
        }
        foreach ((array) $this->to as $address) {
            $mailer->addAddress($submission->interpolate($address));
        }
        if (null !== $this->replyTo && '' !== $replyTo = $submission->interpolate($this->replyTo)) {
            $mailer->addReplyTo($replyTo);
        }

        $mailer->Subject = is_string($this->subject) ? $submission->interpolate($this->subject) : ($this->subject)($submission);
        $body = match (true) {
            null === $this->body => $this->defaultBody($submission),
            is_string($this->body) => $submission->interpolate($this->body),
            default => ($this->body)($submission),
        };
        if ($this->html) {
            $mailer->isHTML();
            $mailer->Body = $body;
            $mailer->AltBody = strip_tags($body);
        } else {
            $mailer->Body = $body;
        }

        if (!$mailer->send()) {
            throw new RuntimeException('Mail could not be sent: ' . $mailer->ErrorInfo);
        }
    }

    private function defaultBody(Submission $submission): string
    {
        $lines = [];
        foreach ($submission->form->fields as $name => $field) {
            if ($field->storesValue()) {
                $lines[] = $field->translatedLabel() . ': ' . $field->textValue($submission->value($name));
            }
        }
        return implode("\n", $lines);
    }
}
