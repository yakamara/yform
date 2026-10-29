<?php

namespace Yakamara\YForm\Backend;

use Redaxo\Core\Config;
use Redaxo\Core\Core;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\Upload;
use Yakamara\YForm\Field\Upload\UploadStorage;
use Yakamara\YForm\Table\Tables;

use function bin2hex;
use function hash_equals;
use function hash_hmac;
use function implode;
use function is_file;
use function is_string;
use function random_bytes;

/**
 * Delivers files of {@see Upload} fields in the backend (`yform/data&func=download`).
 *
 * The links are signed for the current backend user with a secret of the installation: only a page the user could
 * open renders them, so inline records are downloadable even without own permissions on the inline table, and a
 * link passed on to another user does not work for them. Files are always sent as attachment with `nosniff`, a
 * stored HTML or script file is never rendered by the browser.
 *
 * @internal
 */
final class UploadDownload
{
    public static function url(Upload $field, Record $record, string $file): string
    {
        $params = ['table' => $record->table->name, 'id' => (int) $record->id, 'field' => $field->name, 'file' => $file];
        return Url::backendPage('yform/data', ['func' => 'download', ...$params, 'sig' => self::sign($params)]);
    }

    /** Sends the requested file and ends the request; returns only when the link is invalid. */
    public static function send(): void
    {
        $params = [
            'table' => Request::get('table', 'string', ''),
            'id' => Request::get('id', 'int', 0),
            'field' => Request::get('field', 'string', ''),
            'file' => Request::get('file', 'string', ''),
        ];
        if (null === Core::getUser() || !hash_equals(self::sign($params), Request::get('sig', 'string', ''))) {
            return;
        }
        $field = Tables::find($params['table'])?->getFields()[$params['field']] ?? null;
        if (!$field instanceof Upload || $field->mediaPool || basename($params['file']) !== $params['file']) {
            return;
        }
        $path = UploadStorage::recordDir($params['table'], $params['field'], $params['id']) . $params['file'];
        if (!is_file($path)) {
            return;
        }

        Response::setHeader('X-Content-Type-Options', 'nosniff');
        Response::sendFile($path, 'application/octet-stream', 'attachment', $params['file']);
        exit;
    }

    /**
     * The signature can not use a session token: the session is already written when the form renders, a token
     * created then would never be stored.
     *
     * @param array{table: string, id: int, field: string, file: string} $params
     */
    private static function sign(array $params): string
    {
        return hash_hmac('sha256', implode("\0", [...$params, (int) Core::getUser()?->id]), self::secret());
    }

    private static function secret(): string
    {
        $secret = Config::get('yform', 'upload_secret');
        if (!is_string($secret) || '' === $secret) {
            $secret = bin2hex(random_bytes(32));
            Config::set('yform', 'upload_secret', $secret);
        }
        return $secret;
    }
}
