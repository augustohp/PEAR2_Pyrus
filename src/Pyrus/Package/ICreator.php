<?php
/**
 * \pear2\Pyrus\Package\ICreator
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
 * Interface for a Package creator.
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
namespace pear2\Pyrus\Package;
interface ICreator
{
    /**
     * save a file inside this package
     * @param string relative path within the package
     * @param string|resource file contents or open file handle
     */
    function addFile($path, $filename);
    /**
     * Add everything within a directory and all subdirectories
     * @param string path to the directory to add
     */
    function addDir($path);
    /**
     * Initialize the package creator
     */
    function init();
    /**
     * Create an internal directory, creating parent directories as needed
     * @param string $dir
     */
    function mkdir($dir);
    /**
     * Finish saving the package
     */
    function close();
}