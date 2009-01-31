<?php
require_once 'blosxom/AccessURL.php';
require_once 'PHPUnit/Framework.php';

class AccessURLTest extends PHPUnit_Framework_TestCase
{
    public function testSample() {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '8080';
        $_SERVER['SCRIPT_NAME'] = '/user/edit';
        $a = new AccessURL();
        $this->assertEquals("http://localhost:8080/user/edit", $a->url());
    }
}

?>