<?php

namespace Riimu\Kit\FileResponse\Response;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FileResponseTest extends \PHPUnit_Framework_TestCase
{
    private $errorReporting;

    public function setUp()
    {
        $this->errorReporting = error_reporting();
    }

    public function tearDown()
    {
        error_reporting($this->errorReporting);
    }

    public function testETag()
    {
        $filename = FILES_DIR . DIRECTORY_SEPARATOR . 'response.txt';
        $response = new FileResponse($filename);

        $this->assertSame(
            sprintf('%x-%x', filesize($filename), filemtime($filename)),
            $response->getETag()
        );

        $response->enableMd5Tag(true);
        $this->assertSame(md5_file($filename), $response->getETag());

        $response->setETag('');
        $this->assertSame('', $response->getETag());
    }

    public function testLastModified()
    {
        $filename = FILES_DIR . DIRECTORY_SEPARATOR . 'response.txt';
        $response = new FileResponse($filename);

        $this->assertSame(filemtime($filename), $response->getLastModified());

        $response->setLastModified(0);
        $this->assertSame(0, $response->getLastModified());
    }

    public function testName()
    {
        $filename = FILES_DIR . DIRECTORY_SEPARATOR . 'response.txt';
        $response = new FileResponse($filename);

        $this->assertSame('response.txt', $response->getName());
        $this->assertSame('text/plain', $response->getType());

        $response->setName('');
        $this->assertSame('', $response->getName());
        $this->assertSame('application/octet-stream', $response->getType());
    }

    public function testGetLength()
    {
        $filename = FILES_DIR . DIRECTORY_SEPARATOR . 'response.txt';
        $response = new FileResponse($filename);
        $this->assertSame(filesize($filename), $response->getLength());
    }

    public function testOutput()
    {
        $filename = FILES_DIR . DIRECTORY_SEPARATOR . 'response.txt';
        $response = new FileResponse($filename);
        $this->expectOutputString('0123456789');
        $response->output();
    }

    public function testOutputBytes()
    {
        $filename = FILES_DIR . DIRECTORY_SEPARATOR . 'response.txt';
        $response = new FileResponse($filename);
        $this->expectOutputString('456');
        $response->open();
        $response->outputBytes(4, 6);
        $response->close();
    }

    public function testInvalidFile()
    {
        $this->setExpectedException('InvalidArgumentException');
        new FileResponse(FILES_DIR . DIRECTORY_SEPARATOR . 'nonexistantfile');
    }

    public function testFailedOutput()
    {
        $response = new FileResponse(FILES_DIR . '/response.txt');

        $property = new \ReflectionProperty($response, 'path');
        $property->setAccessible(true);
        $property->setValue($response, FILES_DIR . '/not_valid_file');

        error_reporting(E_ERROR);
        $this->setExpectedException('RuntimeException');

        $response->output();
    }

    public function testFailedOpening()
    {
        $response = new FileResponse(FILES_DIR . '/response.txt');

        $property = new \ReflectionProperty($response, 'path');
        $property->setAccessible(true);
        $property->setValue($response, FILES_DIR . '/not_valid_file');

        error_reporting(E_ERROR);
        $this->setExpectedException('RuntimeException');

        $response->open();
    }

    public function testFailedClosing()
    {
        $response = new FileResponse(FILES_DIR . '/response.txt');
        $response->open();

        $property = new \ReflectionProperty($response, 'handle');
        $property->setAccessible(true);
        $property->setValue($response, null);

        error_reporting(E_ERROR);
        $this->setExpectedException('RuntimeException');

        $response->close();
    }

    public function testFailedReading()
    {
        $response = new FileResponse(FILES_DIR . '/response.txt');

        error_reporting(E_ERROR);
        $this->setExpectedException('RuntimeException');

        $response->outputBytes(0, 1);
    }

    public function testTooLongRead()
    {
        $response = new FileResponse(FILES_DIR . '/response.txt');

        error_reporting(E_ERROR);
        $this->setExpectedException('RuntimeException');

        $response->open();
        $response->outputBytes(0, 100);
    }
}
