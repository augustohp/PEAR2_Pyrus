<?php
/**
 * PEAR2_Pyrus_Package_Base
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
 * Base class for representing a package in Pyrus
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
abstract class PEAR2_Pyrus_Package_Base implements PEAR2_Pyrus_IPackage
{
    protected $packagefile;
    /**
     * The original source of this package
     *
     * This is a chain documenting the steps it took to get this
     * package instantiated, for instance Tar->Abstract
     * @var PEAR2_Pyrus_IPackage
     */
    protected $from;

    function __construct(PEAR2_Pyrus_PackageFile $packagefile, $parent = null)
    {
        $this->packagefile = $packagefile;
        $this->from = $parent;
    }

    function isStatic()
    {
        return true;
    }

    /**
     * Used to determine whether a package is designed for Pyrus, or for the PEAR installer
     *
     * @return bool
     */
    function isNewPackage()
    {
        return version_compare($this->dependencies['required']->pearinstaller->min,
                               '2.0.0a1', '>=');
    }

    function isUpgradeable()
    {
        if (!isset(PEAR2_Pyrus_Installer::$options['upgrade'])) {
            // we don't attempt to upgrade a dep unless we're upgrading
            return false;
        }
        $reg = PEAR2_Pyrus_Config::current()->registry;
        $version = $reg->info($this->name, $info->channel, 'version');
        if (version_compare($this->version, $version, '<=')) {
            return false;
        }
        return true;
    }

    /**
     * This test tells the installer whether to run any package-info
     * replacement tasks.
     *
     * The XML package has not had any package-info transformations.  Packages
     * in tar/zip/phar format have had package-info replacements.
     * @return bool if false, the installer will run all packag-einfo replacements
     */
    function isPreProcessed()
    {
        return true;
    }

    function setFrom(PEAR2_Pyrus_IPackage $from)
    {
        $this->from = $from;
    }

    function getFrom()
    {
        if ($this->from) {
            return $this->from->getFrom();
        }

        return $this;
    }

    /**
     * Sort files/directories for removal
     *
     * Files are always removed first, followed by directories in
     * path order
     * @param unknown_type $a
     * @param unknown_type $b
     * @return unknown
     */
    static function sortstuff($a, $b)
    {
        // files can be removed in any order
        if (is_file($a) && is_file($b)) return 0;
        if (is_dir($a) && is_file($b)) return 1;
        if (is_dir($b) && is_file($a)) return -1;
        $countslasha = substr_count($a, DIRECTORY_SEPARATOR);
        $countslashb = substr_count($b, DIRECTORY_SEPARATOR);
        if ($countslasha > $countslashb) return -1;
        if ($countslashb > $countslasha) return 1;
        // if not subdirectories, tehy can be removed in any order
        return 0;
    }

    /**
     * Create vertices/edges of a directed graph for dependencies of this package
     *
     * Iterate over dependencies and create edges from this package to those it
     * depends upon
     * @param PEAR2_Pyrus_DirectedGraph $graph
     * @param array $packages channel/package indexed array of PEAR2_Pyrus_Package objects
     */
    function makeConnections(PEAR2_Pyrus_DirectedGraph $graph, array $packages)
    {
        $graph->add($this->getFrom());
        foreach (array('required', 'optional') as $required) {
            foreach (array('package', 'subpackage') as $package) {
                foreach ($this->dependencies[$required]->$package as $d) {
                    if ($d->conflicts) {
                        continue;
                    }

                    if (isset($packages[$d->channel . '/' . $d->name])) {
                        $graph->connect($this, $packages[$d->channel . '/' . $d->name]);
                    }
                }
            }
        }

        foreach ($this->dependencies['group'] as $group) {
            foreach (array('package', 'subpackage') as $package) {
                foreach ($group->$package as $d) {
                    if ($d->conflicts) {
                        continue;
                    }

                    if (isset($packages[$d->channel . '/' . $d->name])) {
                        $graph->connect($this, $packages[$d->channel . '/' . $d->name]);
                    }
                }
            }
        }
    }

    function offsetExists($offset)
    {
        return $this->packagefile->info->hasFile($offset);
    }

    function offsetGet($offset)
    {
        if (strpos($offset, 'contents://') === 0) {
            return $this->getFileContents(substr($offset, 11));
        }

        return $this->packagefile->info->getFile($offset);
    }

    function offsetSet($offset, $value)
    {
        return;
    }

    function offsetUnset($offset)
    {
        return;
    }

    function getPackageFile()
    {
        return $this->packagefile;
    }

    function __call($func, $args)
    {
        // delegate to the internal object
        return call_user_func_array(array($this->packagefile->info, $func), $args);
    }

    function __get($var)
    {
        return $this->packagefile->info->$var;
    }

    function __set($var, $value)
    {
        return $this->packagefile->info->$var = $value;
    }

    function getValidator()
    {
        return $this->packagefile->info->getValidator();
    }

    function toArray($forpackaging = false)
    {
        return $this->packagefile->info->toArray($forpackaging);
    }

    function __toString()
    {
        return $this->packagefile->__toString();
    }

    function validate($state = PEAR2_Pyrus_Validate::NORMAL)
    {
        $validator = $this->packagefile->getValidator();
        if (!$validator->validate($this, $state)) {
            throw new PEAR2_Pyrus_PackageFile_Exception('Invalid package.xml', $validator->getErrors());
        }
    }

    function getPackageFileObject()
    {
        return $this->packagefile;
    }

    function getFileContents($file, $asstream = false)
    {
        if (!isset($this[$file])) {
            throw new PEAR2_Pyrus_Package_Exception('file ' . $file . ' is not in this package');
        }
        if ($asstream) {
            $fp = fopen($this->getFilePath($file), 'rb');
            return $fp;
        } else {
            $ret = file_get_contents($this->getFilePath($file));
            if (!$ret) {
                $ret = '';
            }
            return $ret;
        }
    }
}
