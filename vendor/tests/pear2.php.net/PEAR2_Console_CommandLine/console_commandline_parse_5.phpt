--TEST--
Test for PEAR2\Console\CommandLine::parse() method (errors 2).
--SKIPIF--
<?php if(php_sapi_name()!='cli') echo 'skip'; ?>
--ARGS--
--float 2>&1
--FILE--
<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tests.inc.php';

try {
    $parser = buildParser1();
    $result = $parser->parse();
} catch (PEAR2\Console\CommandLine\Exception $exc) {
    $parser->displayError($exc->getMessage());
}

?>
--EXPECT--
Error: Option "float" requires a value.
Type "some_program --help" to get help.
