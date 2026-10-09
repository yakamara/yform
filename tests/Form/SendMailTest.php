<?php

namespace Yakamara\YForm\Tests\Form;

use PHPUnit\Framework\TestCase;
use Yakamara\YForm\Form\Action\SendMail;

/**
 * @internal
 */
final class SendMailTest extends TestCase
{
    public function testPlainTextAlternative(): void
    {
        $html = "<p>Hallo Erika &lt;Muster&gt;,</p>\n    <p>danke für <strong>deine</strong> Nachricht.<br>Zweite Zeile</p>";

        self::assertSame("Hallo Erika <Muster>,\n\ndanke für deine Nachricht.\nZweite Zeile", SendMail::plainText($html));
    }
}
