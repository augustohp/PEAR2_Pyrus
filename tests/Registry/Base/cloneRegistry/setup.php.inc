<?php
require __DIR__ . '/../../setup.php.inc';
$c = getTestConfig();
require __DIR__ . '/../../../PackageFile_v2/setupFiles/setupPackageFile.php.inc';
$reg2 = new \PEAR2\Pyrus\Registry\Sqlite3(TESTDIR);
$reg = new \PEAR2\Pyrus\Registry\Pear1(TESTDIR);