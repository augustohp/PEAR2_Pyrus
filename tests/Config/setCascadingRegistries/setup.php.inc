<?php
set_include_path('C:/development/pear-core/testpear/php;C:/php5/pear');
require dirname(dirname(__FILE__)) . '/setup.php.inc';
class r extends PEAR2_Pyrus_Registry
{
    static public $parents = array();
    public $path;
    function setParent(PEAR2_Pyrus_Registry $c)
    {
        self::$parents[$c->path] = true;
        return parent::setParent($c);
    }

    function __construct($path, $registries = array('Sqlite', 'Xml'))
    {
        $this->path = $path;
        return parent::__construct($path, $registries);
    }
}
class c extends PEAR2_Pyrus_ChannelRegistry
{
    static public $parents = array();
    public $path;
    function setParent(PEAR2_Pyrus_ChannelRegistry $c)
    {
        self::$parents[$c->path] = true;
        return parent::setParent($c);
    }

    function __construct($path, $registries = array('Sqlite', 'Xml'))
    {
        $this->path = $path;
        return parent::__construct($path, $registries);
    }
}
PEAR2_Pyrus_Registry::$classname = 'r';
PEAR2_Pyrus_ChannelRegistry::$classname = 'c';