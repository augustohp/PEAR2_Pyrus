<?php
require dirname(__DIR__) . '/setup.php.inc';
class tc extends \PEAR2\Pyrus\Config
{
    public static function getTestDefaults($nophpini = false)
    {
        return self::$defaults;
    }

    public static function constructDefaults()
    {
        return parent::constructDefaults();
    }
}
