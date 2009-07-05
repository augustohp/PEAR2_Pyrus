<?php
/**
 * \pear2\Pyrus\PackageFile
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
 * Base class for a PEAR2 package file
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
namespace pear2\Pyrus;
class PackageFile
{
    public $info;
    public $path;
    function __construct($package, $class = 'pear2\Pyrus\PackageFile\v2', $isstring = false)
    {
        if ($package instanceof \pear2\Pyrus\IPackageFile) {
            $this->path = $package->getFilePath();
            return $this->info = $package;
        }
        $this->path = $package;
        $parser = new \pear2\Pyrus\PackageFile\Parser\v2;
        if ($isstring) {
            $data = $package;
        } else {
            if (file_exists($package)) {
                $data = file_get_contents($package);
            } else {
                $data = false;
            }
        }
        if ($data === false || empty($data)) {
            throw new \pear2\Pyrus\PackageFile\Exception('Unable to open package xml file '
                . $package . ' or file was empty.');
        }
        $this->info = $parser->parse($data, $package, $class);
    }

    function __toString()
    {
        return $this->info->__toString();
    }

    function getValidator()
    {
        return $this->info->getValidator();
    }

    function getPackageFileObject()
    {
        return $this->info;
    }
}
