<?php
/**
 * \pear2\Pyrus\Package\Dependency
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
 * Class represents a package dependency.
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
namespace pear2\Pyrus\Package;
class Dependency extends \pear2\Pyrus\Package\Remote
{
    /**
     * A map of package name => packages that depend on this package =>
     * actual dependency
     */
    static protected $dependencyTree = array();
    
    /**
     * A map of package => packages it depends upon
     */
    static protected $packageDepTree = array();

    static function getPHPVersion()
    {
        return phpversion();
    }

    /**
     * Check to see if any packages in the list of packages to be installed
     * satisfy this dependency, and return one if found, otherwise
     * instantiate a new dependency package object
     * @return \pear2\Pyrus\IPackage
     */
    static function retrieve($installerclass, array $toBeInstalled,
                             \pear2\Pyrus\PackageFile\v2\Dependencies\Package $info,
                             \pear2\Pyrus\Package $parentPackage)
    {
        $reg = \pear2\Pyrus\Config::current()->registry;
        static::processDependencies($info, $parentPackage);
        // first check to see if the dependency is installed
        $canupgrade = false;
        if (isset($reg->package[$info->channel . '/' . $info->name])) {
            if (!isset(\pear2\Pyrus\Main::$options['upgrade'])) {
                // we don't attempt to upgrade a dep unless we're upgrading
                return;
            }
            $version = $reg->info($info->name, $info->channel, 'version');
            $stability = $reg->info($info->name, $info->channel, 'state');
            if ($parentPackage->isRemote() && $parentPackage->getExplicitState()) {
                $installedstability = \pear2\Pyrus\Installer::betterStates($stability);
                $parentstability = \pear2\Pyrus\Installer::betterStates($parentPackage->getExplicitState());
                if (count($parentstability) > count($installedstability)) {
                    $stability = $parentPackage->getExplicitState();
                }
            } else {
                $installedstability = \pear2\Pyrus\Installer::betterStates($stability);
                $prefstability = \pear2\Pyrus\Installer::betterStates(\pear2\Pyrus\Config::current()->preferred_state);
                if (count($prefstability) > count($installedstability)) {
                    $stability = \pear2\Pyrus\Config::current()->preferred_state;
                }
            }
            // see if there are new versions in our stability or better
            $remote = new \pear2\Pyrus\Channel\Remotepackage(\pear2\Pyrus\Config::current()
                                                            ->channelregistry[$info->channel], $stability);
            $found = false;
            foreach ($remote[$info->name] as $remoteversion => $rinfo) {
                if (version_compare($remoteversion, $version, '<=')) {
                    continue;
                }
                if (version_compare($rinfo['minimumphp'], static::getPHPversion(), '>')) {
                    continue;
                }
                // found one, so upgrade is possible if dependencies pass
                $found = true;
                break;
            }
            // the installed package version satisfies this dependency, don't do anything
            if (!$found) {
                return;
            }
            $canupgrade = true;
        }
        if (isset($toBeInstalled[$info->channel . '/' . $info->name])) {
            $ret = $toBeInstalled[$info->channel . '/' . $info->name];
            if ($parentPackage->isRemote() && $parentPackage->getExplicitState()
                && $ret->isRemote() && !$ret->getExplicitState()) {
                $ret->setExplicitState($parentPackage->getExplicitState());
            }
            if ($ret->isRemote() && $canupgrade) {
                $ret->setUpgradeable();
            }
            return $installerclass::prepare($ret);
        }
        if (isset($info->uri)) {
            $ret = new \pear2\Pyrus\Package\Remote($info->uri);
            // set up the basics
            $ret->name = $info->name;
            $ret->uri = $info->uri;
            return $installerclass::prepare($ret);
        }
        if ($parentPackage->isRemote() && $parentPackage->getExplicitState()) {
            // pass the same explicit state to the child dependency
            $ret = new \pear2\Pyrus\Package\Remote($info->channel . '/' . $info->name . '-' .
                                                  $parentPackage->getExplicitState());
            if ($canupgrade) {
                $ret->setUpgradeable();
            }
            return $installerclass::prepare($ret);
        }
        $ret = new \pear2\Pyrus\Package\Remote($info->channel . '/' . $info->name);
        if ($canupgrade) {
            $ret->setUpgradeable();
        }
        return $installerclass::prepare($ret);
    }

    /**
     * Create a tree mapping packages to those that depend on them
     *
     * This is used to determine which versions of a package satisfy
     * all package dependencies.  A composite dependency is calculated
     * from all of them, and this also allows removing a package from the
     * calculation
     */
    static protected function processDependencies($info, $parentPackage)
    {
        static::$dependencyTree[$info->channel . '/' . $info->name]
                               [$parentPackage->channel . '/' . $parentPackage->name] = $info;
        static::$packageDepTree[$parentPackage->channel . '/' . $parentPackage->name]
            [$info->channel . '/' . $info->name] = 1;
    }

    /**
     * @return array A list of packages to be removed from the to-be-installed list
     */
    static function removePackage(\pear2\Pyrus\Package $info)
    {
        if (!isset(static::$packageDepTree[$info->channel . '/' . $info->name])) {
            return array();
        }
        $ret = array();
        foreach (static::$packageDepTree[$info->channel . '/' . $info->name] as $package => $unused) {
            unset(static::$dependencyTree[$package][$info->channel . '/' . $info->name]);
            if (!count(static::$dependencyTree[$package])) {
                $ret[] = $package;
                unset(static::$dependencyTree[$package]);
            }
        }
        unset(static::$packageDepTree[$info->channel . '/' . $info->name]);
        return $ret;
    }

    /**
     * Return a composite dependency on the package, as defined by combining
     * all dependencies on this package into one.
     *
     * As an example, for these dependencies:
     *
     * <pre>
     * P1 version >= 1.2.0
     * P1 version <= 3.0.0, != 2.3.2
     * P1 version >= 1.1.0, != 1.2.0
     * </pre>
     *
     * The composite dependency is
     *
     * <pre>
     * P1 version >= 1.2.0, <= 3.0.0, != 2.3.2, 1.2.0
     * </pre>
     */
    static function getCompositeDependency(\pear2\Pyrus\Package $info)
    {
        if (!isset(static::$dependencyTree[$info->channel . '/' . $info->name])) {
            return new \pear2\Pyrus\PackageFile\v2\Dependencies\Package(
                'required', 'package', null, array('name' => $info->name, 'channel' => $info->channel, 'uri' => null,
                                            'min' => null, 'max' => null,
                                            'recommended' => null, 'exclude' => null,
                                            'providesextension' => null, 'conflicts' => null), 0);
        }
        $compdep = array('name' => $info->name, 'channel' => $info->channel, 'uri' => null,
                                            'min' => null, 'max' => null,
                                            'recommended' => null, 'exclude' => null,
                                            'providesextension' => null, 'conflicts' => null);
        $initial = true;
        $recommended = null;
        $min = null;
        $max = null;
        foreach (static::$dependencyTree[$info->channel . '/' . $info->name] as $deppackage => $actualdep) {
            if ($initial) {
                if ($actualdep->min) {
                    $compdep['min'] = $actualdep->min;
                    $min = $deppackage;
                }
                if ($actualdep->max) {
                    $compdep['max'] = $actualdep->max;
                    $max = $deppackage;
                }
                if ($actualdep->recommended) {
                    $compdep['recommended'] = $actualdep->recommended;
                    $recommended = $deppackage;
                }
                $compdep['exclude'] = $actualdep->exclude;
                $initial = false;
                continue;
            }
            if (isset($compdep['recommended']) && isset($actualdep->recommended)
                && $actualdep->recommended != $compdep['recommended']) {
                throw new \pear2\Pyrus\Package\Exception('Cannot install ' . $info->channel . '/' .
                    $info->name . ', two dependencies conflict (different recommended values for ' .
                    $deppackage . ' and ' . $recommended . ')');
            }
            if ($compdep['max'] && $actualdep->min && version_compare($actualdep->min, $compdep['max'], '>')) {
                throw new \pear2\Pyrus\Package\Exception('Cannot install ' . $info->channel . '/' .
                    $info->name . ', two dependencies conflict (' .
                    $deppackage . ' min is > ' . $max . ' max)');
            }
            if ($compdep['min'] && $actualdep->max && version_compare($actualdep->max, $compdep['min'], '<')) {
                throw new \pear2\Pyrus\Package\Exception('Cannot install ' . $info->channel . '/' .
                    $info->name . ', two dependencies conflict (' .
                    $deppackage . ' max is < ' . $min . ' min)');
            }
            if ($actualdep->min) {
                if ($compdep['min']) {
                    if (version_compare($actualdep->min, $compdep['min'], '>')) {
                        $compdep['min'] = $actualdep->min;
                        $min = $deppackage;
                    }
                } else {
                    $compdep['min'] = $actualdep->min;
                    $min = $deppackage;
                }
            }
            if ($actualdep->max) {
                if ($compdep['max']) {
                    if (version_compare($actualdep->max, $compdep['max'], '<')) {
                        $compdep['max'] = $actualdep->max;
                        $max = $deppackage;
                    }
                } else {
                    $compdep['max'] = $actualdep->max;
                    $max = $deppackage;
                }
            }
            if ($actualdep->recommended) {
                $compdep['recommended'] = $actualdep->recommended;
                $recommended = $deppackage;
            }
            if ($actualdep->exclude) {
                if (!$compdep['exclude']) {
                    $compdep['exclude'] = array();
                    foreach ($actualdep->exclude as $exclude) {
                        $compdep['exclude'][] = $exclude;
                    }
                    continue;
                }
                foreach ($actualdep->exclude as $exclude) {
                    if (in_array($exclude, $compdep['exclude'])) {
                        continue;
                    }
                    $compdep['exclude'][] = $exclude;
                }
            }
        }
        return new \pear2\Pyrus\PackageFile\v2\Dependencies\Package(
            'required', 'package', null, $compdep, 0);
    }
}