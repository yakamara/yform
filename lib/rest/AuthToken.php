<?php

namespace Yakamara\YForm\Rest;

use rex;
use rex_sql;
use rex_sql_exception;

use function count;

class AuthToken
{
    public static $interval = [
        'none',
        'overall',
        'hour',
        'day',
        'month',
    ];
    public static $tokenList = [];

    /**
     * @throws rex_sql_exception
     */
    public static function checkToken(Route $route): bool
    {
        $myToken = Rest::getHeader('token');

        $TokenAuths = rex_sql::factory()->getArray('select * from ' . rex::getTable('yform_rest_token') . ' where status=1 and token=? and FIND_IN_SET(?, paths)', [$myToken, $route->getPath()]);

        if (1 != count($TokenAuths)) {
            return false;
        }

        $TokenAuth = $TokenAuths[0];

        $return = false;
        switch ($TokenAuth['interval']) {
            case 'none':
                $return = true;
                break;

            default:
                $hits = self::getCurrentIntervalAmount((string) $TokenAuth['interval'], $TokenAuth['id']);
                if ($hits < $TokenAuth['amount']) {
                    $return = true;
                }
                break;
        }

        if ($return) {
            self::addHit($TokenAuth);
            return true;
        }

        return false;
    }

    /**
     * @throws rex_sql_exception
     */
    public static function addHit(array $TokenAuth)
    {
        rex_sql::factory()
            ->setTable(rex::getTable('yform_rest_token_access'))
            ->setValue('token_id', $TokenAuth['id'])
            ->setValue('datetime_created', date(rex_sql::FORMAT_DATETIME))
            ->setValue('url', Rest::getCurrentUrl())
            ->insert();
    }

    /**
     * @throws rex_sql_exception
     * @return mixed|null
     */
    public static function get(int $id)
    {
        if (0 == count(self::$tokenList)) {
            self::$tokenList = rex_sql::factory()->getArray('select * from ' . rex::getTable('yform_rest_token'));
        }

        foreach (self::$tokenList as $token) {
            if ($token['id'] == $id) {
                return $token;
            }
        }
        return null;
    }

    /**
     * @throws rex_sql_exception
     * @return mixed|null
     */
    public static function getCurrentIntervalAmount(string $interval, $token_id)
    {
        switch ($interval) {
            case 'month':
                $count = rex_sql::factory()->setQuery('select count(*) as c from ' . rex::getTable('yform_rest_token_access') . ' where token_id = ? and datetime_created LIKE ?', [$token_id, date('Y-m-') . '%']);
                break;
            case 'day':
                $count = rex_sql::factory()->setQuery('select count(*) as c from ' . rex::getTable('yform_rest_token_access') . ' where token_id = ? and datetime_created LIKE ?', [$token_id, date('Y-m-d ') . '%']);
                break;
            case 'hour':
                $count = rex_sql::factory()->setQuery('select count(*) as c from ' . rex::getTable('yform_rest_token_access') . ' where token_id = ? and datetime_created LIKE ?', [$token_id, date('Y-m-d H:') . '%']);
                break;
            case 'overall':
            default:
                $count = rex_sql::factory()->setQuery('select count(*) as c from ' . rex::getTable('yform_rest_token_access') . ' where token_id = ?', [$token_id]);
                break;
        }

        return $count->getValue('c');
    }
}
