<?php


class rex_yform_rest_auth_token
{
    public static $Tokens = [];

    public static function checkToken()
    {
        $myToken = \rex_yform_rest::getHeader('token');

        if (array_key_exists($myToken, self::$Tokens)) {
            return self::$Tokens[$myToken];
        }

        $tokens = \rex_sql::factory()->getArray('select id from rex_yform_rest_token where status=1 and token=?', [$myToken]);

        self::$Tokens[$myToken] = false;
        if (count($tokens) == 1) {
            self::$Tokens[$myToken] = true;
        }

        return self::$Tokens[$myToken];
    }
}
