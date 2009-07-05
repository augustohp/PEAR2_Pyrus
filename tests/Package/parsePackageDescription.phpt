--TEST--
\pear2\Pyrus\Package::parsePackageDescription()
--FILE--
<?php
include __DIR__ . '/setup.minimal.php.inc';
mkdir($d = __DIR__ . '/testit');
file_put_contents($d . '/foob', '<?xml version="1.0" ?>
                  <package version="2.0">');

$test->assertEquals('pear2\Pyrus\Package\Xml', \pear2\Pyrus\Package::parsePackageDescription($d . '/foob'),
                    'no extension, xml');

file_put_contents($d . '/foob', 'not xml');

$test->assertEquals('pear2\Pyrus\Package\Phar', \pear2\Pyrus\Package::parsePackageDescription($d . '/foob'),
                    'no extension, not xml');

$test->assertEquals('pear2\Pyrus\Package\Remote', \pear2\Pyrus\Package::parsePackageDescription('http://blah'),
                    'http');

$test->assertEquals('pear2\Pyrus\Package\Remote', \pear2\Pyrus\Package::parsePackageDescription('https://blah'),
                    'https');

$test->assertEquals('pear2\Pyrus\Package\Remote', \pear2\Pyrus\Package::parsePackageDescription('foob', true),
                    'force remote');

try {
    \pear2\Pyrus\Package::parsePackageDescription('Yorng/$%#');
    throw new Exception('should have failed');
} catch (\pear2\Pyrus\Package\Exception $e) {
    $test->assertEquals('package "Yorng/$%#" is unknown', $e->getMessage(), 'Yorng/$%%#');
    $test->assertEquals('Unable to process package name', $e->getCause()->getMessage(), 'name parse error');
}
?>
===DONE===
--CLEAN--
<?php
$dir = __DIR__ . '/testit';
include __DIR__ . '/../clean.php.inc';
?>
--EXPECT--
===DONE===