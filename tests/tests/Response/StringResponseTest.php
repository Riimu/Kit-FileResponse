<?php

namespace Riimu\Kit\FileResponse\Response;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StringResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $response = new StringResponse('0123456789', 'file.txt');
        $this->assertSame('file.txt', $response->getName());
        $this->assertSame('text/plain', $response->getType());
    }

    public function testETag()
    {
        $response = new StringResponse('0123456789');
        $this->assertSame(md5('0123456789'), $response->getETag());

        $response->setETag('');
        $this->assertSame('', $response->getETag());
    }

    public function testGetLength()
    {
        $response = new StringResponse('0123456789');
        $this->assertSame(10, $response->getLength());
    }

    public function testOutput()
    {
        $response = new StringResponse('0123456789');
        $this->expectOutputString('0123456789');
        $response->output();
    }

    public function testOutputBytes()
    {
        $response = new StringResponse('0123456789');
        $this->expectOutputString('456');
        $response->outputBytes(4, 6);
    }
}
