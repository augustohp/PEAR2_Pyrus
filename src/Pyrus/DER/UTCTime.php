<?php
/**
 * PEAR2_Pyrus_DER_UTCTime
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
 * Represents a Distinguished Encoding Rule UTC Time
 * 
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
class PEAR2_Pyrus_DER_UTCTime extends PEAR2_Pyrus_DER
{
    const TAG = 0x17;
    protected $value;

    function __construct(DateTime $date = null)
    {
        $this->setValue($date);
    }

    function setValue(DateTime $date = null)
    {
        if ($date === null) {
            $date = date_create();
        }
        $date->setTimezone(new DateTimeZone('UTC'));
        $this->value = $date;
    }

    function serialize()
    {
        $value = $this->value->format('YmdHis');
        $value .= 'Z';

        return $this->prependTLV($value, strlen($value));
    }

    function parse($data, $location)
    {
        $ret = parent::parse($data, $location);
        $this->value = new DateTime($this->value);
        return $ret;
    }

    function valueToString()
    {
        return $this->value->format('YmdHis') . 'Z';
    }
}