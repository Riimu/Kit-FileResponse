<?php

namespace Riimu\Kit\FileResponse\Response;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class AbstractResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testName()
    {
        $mock = $this->getInstance();
        $this->assertNull($mock->getName());

        $mock->setName('');
        $this->assertSame('', $mock->getName());

        $mock->setName(1);
        $this->assertSame('1', $mock->getName());

        $mock->setName('file.txt');
        $this->assertSame('file.txt', $mock->getName());

        $mock->setName(null);
        $this->assertNull($mock->getName());
    }

    public function testETag()
    {
        $mock = $this->getInstance();
        $this->assertNull($mock->getETag());

        $mock->setETag('');
        $this->assertSame('', $mock->getETag());

        $mock->setETag(1);
        $this->assertSame('1', $mock->getETag());

        $mock->setETag('abcdef');
        $this->assertSame('abcdef', $mock->getETag());

        $mock->setETag(null);
        $this->assertNull($mock->getETag());
    }

    public function testLastModified()
    {
        $mock = $this->getInstance();
        $this->assertNull($mock->getLastModified());

        $mock->setLastModified(0);
        $this->assertSame(0, $mock->getLastModified());

        $mock->setLastModified('12');
        $this->assertSame(12, $mock->getLastModified());

        $mock->setLastModified(new \DateTime());
        $this->assertSame(time(), $mock->getLastModified());

        $mock->setLastModified(null);
        $this->assertNull($mock->getLastModified());
    }

    public function testMaxAge()
    {
        $mock = $this->getInstance();
        $this->assertNull($mock->getMaxAge());

        $mock->setMaxAge(0);
        $this->assertSame(0, $mock->getMaxAge());

        $mock->setExpires(time() + 60);
        $this->assertSame(60, $mock->getMaxAge());

        $mock->setMaxAge(null);
        $this->assertNull($mock->getMaxAge());

        $mock->setMaxAge(1556);
        $this->assertSame(1556, $mock->getMaxAge());

        $mock->setExpires(null);
        $this->assertNull($mock->getMaxAge());
    }

    public function testType()
    {
        $mock = $this->getInstance();
        $this->assertSame('application/octet-stream', $mock->getType());

        $mock->setType('type');
        $this->assertSame('type', $mock->getType());

        $mock->setType(null);
        $this->assertSame('application/octet-stream', $mock->getType());

        $mock->setName('file.txt');
        $this->assertSame('text/plain', $mock->getType());

        $mock->setName('');
        $this->assertSame('application/octet-stream', $mock->getType());

        $mock->setName('image.png');
        $this->assertSame('image/png', $mock->getType());

        $mock->setName('noext');
        $this->assertSame('application/octet-stream', $mock->getType());
    }

    private function getInstance()
    {
        return $this->getMock('Riimu\Kit\FileResponse\Response\AbstractResponse', ['output', 'outputBytes', 'getLength']);
    }
}
