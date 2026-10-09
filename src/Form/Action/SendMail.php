<?php

namespace Yakamara\YForm\Form\Action;

use Closure;
use Redaxo\Core\Core;
use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\MediaPool\Media;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Yakamara\YForm\View\TemplateRenderer;

use function is_file;
use function is_string;
use function strip_tags;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * Sends an e-mail — the successor of the YForm 5 actions `email` and `tpl2email`. Attach it several times for
 * several mails (e.g. notification plus confirmation).
 *
 * Addresses, subject and string bodies may contain `{field}` placeholders (plain text values). The body comes from
 *  - `template`: a PHP file returning `function (Submission $s): void { … }` — the output is the (HTML) body,
 *  - `body`: a string template or closure `fn (Submission $s): string`,
 *  - nothing: a plain list of all fields.
 *
 * ```php
 * new SendMail(to: 'info@example.org', subject: 'Anfrage von {name}', replyTo: '{email}');
 * new SendMail(to: '{email}', subject: 'Danke, {name}', template: __DIR__ . '/mail/thanks.php', attachments: ['agb.pdf']);
 * ```
 */
final class SendMail extends Action
{
    /**
     * @param string|list<string> $to
     * @param string|(Closure(Submission): string) $subject
     * @param string|(Closure(Submission): string)|null $body
     * @param string|null $template path to a PHP template (see class description); implies HTML
     * @param list<string>|(Closure(Submission): list<string>) $attachments media pool file names or absolute paths
     * @param string|list<string> $cc
     * @param string|list<string> $bcc
     */
    public function __construct(
        public readonly string|array $to,
        public readonly string|Closure $subject,
        public readonly string|Closure|null $body = null,
        public readonly ?string $template = null,
        public readonly ?string $replyTo = null,
        public readonly ?string $from = null,
        public readonly ?string $fromName = null,
        public readonly string|array $cc = [],
        public readonly string|array $bcc = [],
        public readonly array|Closure $attachments = [],
        public readonly bool $html = false,
    ) {}

    public function execute(Submission $submission): void
    {
        $email = new Email();
        if (null !== $this->from) {
            $email->from(new Address($submission->interpolate($this->from), $submission->interpolate((string) $this->fromName)));
        }
        foreach ((array) $this->to as $address) {
            $email->addTo($submission->interpolate($address));
        }
        foreach ((array) $this->cc as $address) {
            $email->addCc($submission->interpolate($address));
        }
        foreach ((array) $this->bcc as $address) {
            $email->addBcc($submission->interpolate($address));
        }
        if (null !== $this->replyTo && '' !== $replyTo = $submission->interpolate($this->replyTo)) {
            $email->addReplyTo($replyTo);
        }

        $email->subject(is_string($this->subject) ? $submission->interpolate($this->subject) : ($this->subject)($submission));

        $html = $this->html || null !== $this->template;
        $body = match (true) {
            null !== $this->template => TemplateRenderer::render($this->template, $submission),
            null === $this->body => $this->defaultBody($submission),
            is_string($this->body) => $html ? $submission->interpolateHtml($this->body) : $submission->interpolate($this->body),
            default => ($this->body)($submission),
        };
        if ($html) {
            $email->html($body)->text(self::plainText($body));
        } else {
            $email->text($body);
        }

        foreach ($this->attachments instanceof Closure ? ($this->attachments)($submission) : $this->attachments as $file) {
            $path = is_file($file) ? $file : (null !== Media::get($file) ? Path::media($file) : null);
            if (null === $path) {
                throw new RuntimeException('Mail attachment "' . $file . '" not found.');
            }
            $email->attachFromPath($path);
        }

        try {
            Core::getMailer()->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException('Mail could not be sent: ' . $e->getMessage(), 0, $e);
        }
    }

    /** Plain text alternative of an HTML body: tags removed, entities decoded, indentation trimmed. */
    public static function plainText(string $html): string
    {
        $text = preg_replace('~<br\s*/?>|</p>|</div>|</li>|</h[1-6]>~i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/^[ \t]+|[ \t]+$/m', '', $text) ?? $text;
        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
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
