<?php

namespace Riimu\Kit\FileResponse;

use Riimu\Kit\FileResponse\Response\StringResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class PartialContentTest extends \PHPUnit_Framework_TestCase
{
    public function testMissingRangeHeader()
    {
        $this->assertFalse($this->getPartialContent([])->send());
    }

    public function testInvalidRanges()
    {
        $this->assertFalse($this->getPartialContent(['range' => 'foobar'])->send());
        $this->assertFalse($this->getPartialContent(['range' => 'bytes=-,-'])->send());
        $this->assertFalse($this->getPartialContent(['range' => 'bytes=1-0'])->send());
    }

    public function testUnsatisfiableResponse()
    {
        $this->assertTrue($this->getPartialContent(
            ['range' => 'bytes=1000-2000'],
            [416, 'Requested Range Not Satisfiable'],
            [['Content-Range', 'bytes */200']]
        )->send());
    }

    public function testSimpleRangeRequest()
    {
        $this->expectOutputString('0506070809');
        $this->assertTrue($this->getPartialContent(
            ['range' => 'bytes=10-19'],
            [206, 'Partial Content'],
            [
                ['Accept-Ranges', 'bytes'],
                ['Content-Type', 'text/plain'],
                ['Content-Length', 10],
                ['Content-Range', 'bytes 10-19/200'],
                ['Cache-Control', 'private, max-age=0, no-cache, no-store'],
                ['Pragma', 'no-cache'],
            ]
        )->send());
    }

    public function testLastBytes()
    {
        $this->expectOutputString('949596979899');
        $this->assertTrue($this->getPartialContent(
            ['range' => 'bytes=-11'],
            [206, 'Partial Content'],
            [
                ['Accept-Ranges', 'bytes'],
                ['Content-Type', 'text/plain'],
                ['Content-Length', 12],
                ['Content-Range', 'bytes 188-199/200'],
                ['Cache-Control', 'private, max-age=0, no-cache, no-store'],
                ['Pragma', 'no-cache'],
            ]
        )->send());
    }

    public function testStartingFromByte()
    {
        $this->expectOutputString('9596979899');
        $this->assertTrue($this->getPartialContent(
            ['range' => 'bytes=190-'],
            [206, 'Partial Content'],
            [
                ['Accept-Ranges', 'bytes'],
                ['Content-Type', 'text/plain'],
                ['Content-Length', 10],
                ['Content-Range', 'bytes 190-199/200'],
                ['Cache-Control', 'private, max-age=0, no-cache, no-store'],
                ['Pragma', 'no-cache'],
            ]
        )->send());
    }

    public function testSingleMultiRange()
    {
        $this->expectOutputRegex(
            "#^\r\n--b[0-9a-zA-Z]{31}\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Range: bytes 10-19/200\r\n\r\n" .
            "0506070809\r\n" .
            "--b[0-9a-zA-Z]{31}--\r\n$#"
        );

        $this->assertTrue($this->getPartialContent(
            ['range' => 'bytes=10-19,1000-2000'],
            [206, 'Partial Content'],
            [
                ['Accept-Ranges', 'bytes'],
                ['Content-Type', $this->stringStartsWith('multipart/byteranges; boundary=b')],
                ['Content-Length', 148],
                ['Cache-Control', 'private, max-age=0, no-cache, no-store'],
                ['Pragma', 'no-cache'],
            ]
        )->send());
    }

    public function testRangeCoalescing()
    {
        $this->expectOutputRegex(
            "#^\r\n--b[0-9a-zA-Z]{31}\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Range: bytes 10-29/200\r\n\r\n" .
            "05060708091011121314\r\n" .
            "--b[0-9a-zA-Z]{31}--\r\n$#"
        );

        $this->assertTrue($this->getPartialContent(
            ['range' => 'bytes=25-29,10-19'],
            [206, 'Partial Content'],
            [
                ['Accept-Ranges', 'bytes'],
                ['Content-Type', $this->stringStartsWith('multipart/byteranges; boundary=b')],
                ['Content-Length', 158],
                ['Cache-Control', 'private, max-age=0, no-cache, no-store'],
                ['Pragma', 'no-cache'],
            ]
        )->send());
    }

    public function testMultipleRangesWithOrder()
    {
        $this->expectOutputRegex(
            "#^\r\n--b[0-9a-zA-Z]{31}\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Range: bytes 190-199/200\r\n\r\n" .
            "9596979899\r\n" .
            "--b[0-9a-zA-Z]{31}\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Range: bytes 0-9/200\r\n\r\n" .
            "0001020304\r\n" .
            "--b[0-9a-zA-Z]{31}--\r\n$#"
        );

        $this->assertTrue($this->getPartialContent(
            ['range' => 'bytes=190-199,0-9'],
            [206, 'Partial Content'],
            [
                ['Accept-Ranges', 'bytes'],
                ['Content-Type', $this->stringStartsWith('multipart/byteranges; boundary=b')],
                ['Content-Length', 256],
                ['Cache-Control', 'private, max-age=0, no-cache, no-store'],
                ['Pragma', 'no-cache'],
            ]
        )->send());
    }

    private function getPartialContent($headerValues, $status = null, $sentHeaders = null)
    {
        $headers = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'setHeader', 'setStatus']);
        $param = new \ReflectionProperty('Riimu\Kit\FileResponse\Headers', 'headers');
        $param->setAccessible(true);
        $param->setValue($headers, $headerValues);

        if ($status !== null) {
            $headers->expects($this->once())->method('setStatus')->with($status[0], $status[1]);
        }
        if ($sentHeaders !== null) {
            call_user_func_array(
                [$headers->expects($this->exactly(count($sentHeaders)))->method('setHeader'), 'withConsecutive'],
                $sentHeaders
            );
        }

        $response = new StringResponse(
            '000102030405060708091011121314151617181920212223242526272829' .
            '303132333435363738394041424344454647484950515253545556575859' .
            '606162636465666768697071727374757677787980818283848586878889' .
            '90919293949596979899',
            'content.txt'
        );
        $response->setETag('');

        return new PartialContent($response, $headers);
    }
}
