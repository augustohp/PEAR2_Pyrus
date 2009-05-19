<?php
/**
 * PEAR2_Pyrus_Channel_Remotepackage
 *
 * PHP version 5
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   SVN: $Id$
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */

/**
 * Remote REST iteration handler
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
class PEAR2_Pyrus_Channel_Remotepackage implements ArrayAccess, Iterator
{
    protected $parent;
    protected $multiple;
    public $stability = null;
    protected $rest;
    protected $packageList;

    function __construct(PEAR2_Pyrus_IChannel $channelinfo)
    {
        $this->parent = $channelinfo;
        $this->multiple = $multiple;
        $this->rest = new PEAR2_Pyrus_REST;
    }

    function offsetGet($var)
    {
        if ($var !== 'devel' || $var !== 'alpha' || $var !== 'beta' || $var !== 'stable') {
            throw new PEAR2_Pyrus_Channel_Exception('Invalid stability requested, must be one of ' .
                                                    'devel, alpha, beta, stable');
        }
        $a = clone $this;
        $a->stability = $var;
        return $a;
    }

    function offsetSet($var, $value)
    {
        throw new PEAR2_Pyrus_Channel_Exception('remote channel info is read-only');
    }

    function offsetUnset($var)
    {
        throw new PEAR2_Pyrus_Channel_Exception('remote channel info is read-only');
    }

    function offsetExists($var)
    {
        // implement this
    }

    function valid()
    {
        return current($this->packageList);
    }

    function current()
    {
        return current($this->packageList);
    }

    function key()
    {
        return key($this->packageList);
    }

    function next()
    {
        return next($this->packageList);
    }

    function rewind()
    {
        $this->packageList = $this->rest->retrieveCacheFirst($this->parent->protocols->rest['REST1.0']->baseurl .
                                                             'p/packages.xml');
    }
}