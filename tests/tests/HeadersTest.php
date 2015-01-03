<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class HeadersTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRequestMethod()
    {
        $headers = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertSame('GET', $headers->getRequestMethod());
        $_SERVER['REQUEST_METHOD'] = 'post';
        $this->assertSame('POST', $headers->getRequestMethod());
        unset($_SERVER['REQUEST_METHOD']);
        $this->assertSame(null, $headers->getRequestMethod());
    }

    public function testLoadHeaders()
    {
        $headers = new Headers();
        $this->assertSame([], $this->getHeaders($headers));

        $_SERVER['HTTP_USER_AGENT'] = 'mozilla';
        $headers = new Headers();
        $this->assertSame(['user-agent' => 'mozilla'], $this->getHeaders($headers));

        defineApacheRequestHeaders();

        $headers = new Headers();
        $this->assertSame(['host' => 'www.example.com'], $this->getHeaders($headers));
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    public function testSetCacheHeadersWithoutMaxAge()
    {
        $time = time();
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);
        $mock->expects($this->exactly(4))->method('setHeader')->withConsecutive(
            ['Cache-Control', 'public, max-age=0, no-cache'],
            ['Pragma', 'no-cache'],
            ['Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $time)],
            ['ETag', '"foo\\"bar"']
        );

        $mock->setCacheHeaders($time, 'foo"bar');
    }

    public function testSetCacheHeadersWithMaxAge()
    {
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);
        $mock->expects($this->exactly(2))->method('setHeader')->withConsecutive(
            ['Cache-Control', 'private, max-age=0, no-cache, no-store'],
            ['Pragma', 'no-cache']
        );

        $mock->setCacheHeaders(null, null, 0);
    }

    public function testSetCacheHeadersWithoutCaching()
    {
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);
        $mock->expects($this->exactly(2))->method('setHeader')->withConsecutive(
            ['Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 12)],
            ['Cache-Control', 'public, max-age=12']
        );

        $mock->setCacheHeaders(null, null, 12);
    }

    public function testReadingHeaders()
    {
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);
        $param = new \ReflectionProperty('Riimu\Kit\FileResponse\Headers', 'headers');
        $param->setAccessible(true);
        $param->setValue($mock, ['foo' => 'bar']);

        $this->assertSame('bar', $mock['foo']);
        $this->assertSame('bar', $mock['FoO']);
        $this->assertTrue(isset($mock['foo']));
        $this->assertTrue(isset($mock['FoO']));
        $this->assertFalse(isset($mock['bar']));
    }

    public function testGettingInvalidHeader()
    {
        $this->setExpectedException('InvalidArgumentException');
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);
        $mock['foo'];
    }

    public function testSettingRequestHeaders()
    {
        $this->setExpectedException('RuntimeException');
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);
        $mock['foo'] = 'bar';
    }

    public function testUnSettingRequestHeaders()
    {
        $this->setExpectedException('RuntimeException');
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);
        unset($mock['foo']);
    }

    private function getHeaders($headers)
    {
        $param = new \ReflectionProperty('Riimu\Kit\FileResponse\Headers', 'headers');
        $param->setAccessible(true);
        return $param->getValue($headers);
    }
}
