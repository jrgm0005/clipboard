<?php

class Helper
{
    const LOG_PATH = 'log.log';
    public static function saveInLog($message = '', $object = null)
    {
        if (empty($object)) {
            $msj = $message;
        } else {
            $msj = $message . " : " . var_export($object, true);
        }
        if (!empty($msj)) {
            error_log($msj, 3, self::LOG_PATH);
            error_log(PHP_EOL, 3, self::LOG_PATH);
        }
    }
}
