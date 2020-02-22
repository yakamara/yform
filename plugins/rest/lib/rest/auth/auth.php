<?php

class rex_yform_rest_auth_token
{
    public static $interval = [
        'none',
        'overall',
        'hour',
        'day',
        'month',
    ];
    public static $tokenList = [];

    public static function checkToken(rex_yform_rest_route $route)
    {
        $myToken = \rex_yform_rest::getHeader('token');

        $TokenAuths = \rex_sql::factory()->getArray('select * from '.rex::getTable('yform_rest_token').' where status=1 and token=? and FIND_IN_SET(?, paths)', [$myToken, $route->getPath()]);

        if (count($TokenAuths) != 1) {
            return false;
        }

        $TokenAuth = $TokenAuths[0];

        $return = false;
        switch ($TokenAuth['interval']) {
            case 'none':
                $return = true;
                break;

            default:
                $hits = self::getCurrentIntervalAmount($TokenAuth['interval'], $TokenAuth['id']);
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

    public static function addHit($TokenAuth)
    {
        \rex_sql::factory()
            ->setTable(rex::getTable('yform_rest_token_access'))
            ->setValue('token_id', $TokenAuth['id'])
            ->setValue('datetime_created', date('Y-m-d H:i:s'))
            ->setValue('url', \rex_yform_rest::getCurrentUrl())
            ->insert();
    }

    public static function get($id)
    {
        if (count(self::$tokenList) == 0) {
            self::$tokenList = rex_sql::factory()->getArray('select * from '.rex::getTable('yform_rest_token'));
        }

        foreach (self::$tokenList as $token) {
            if ($token['id'] == $id) {
                return $token;
            }
        }
        return null;
    }

    public static function getCurrentIntervalAmount($interval, $token_id)
    {
        switch ($interval) {
            case 'month':
                $count = rex_sql::factory()->setQuery('select count(*) as c from '.rex::getTable('yform_rest_token_access').' where token_id = ? and datetime_created LIKE ?', [$token_id, date('Y-m-').'%']);
                break;
            case 'day':
                $count = rex_sql::factory()->setQuery('select count(*) as c from '.rex::getTable('yform_rest_token_access').' where token_id = ? and datetime_created LIKE ?', [$token_id, date('Y-m-d ').'%']);
                break;
            case 'hour':
                $count = rex_sql::factory()->setQuery('select count(*) as c from '.rex::getTable('yform_rest_token_access').' where token_id = ? and datetime_created LIKE ?', [$token_id, date('Y-m-d H:').'%']);
                break;
            case 'overall':
            default:
                $count = rex_sql::factory()->setQuery('select count(*) as c from '.rex::getTable('yform_rest_token_access').' where token_id = ?', [$token_id]);
                break;
        }

        return $count->getValue('c');
    }
}
