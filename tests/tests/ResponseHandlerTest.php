<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ResponseHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testHeadersAlreadySent()
    {
        $headers = $this->getMock('Riimu\Kit\FileResponse\Headers', ['headersSent']);
        $headers->expects($this->once())->method('headersSent')->will($this->returnValue(true));

        $handler = new ResponseHandler($headers);

        $this->setExpectedException('RuntimeException');
        $handler->send($this->getMock('Riimu\Kit\FileResponse\Response\Response'));
    }

    public function testNormalResponseWithoutConditionals()
    {
        $headers = $this->getHeaders([200, 'OK'], [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'text/plain',
            'Content-Length' => 100,
            'Cache-Control' => 'private, max-age=0, no-cache, no-store',
            'Pragma' => 'no-cache',
        ]);

        $response = $this->getResponse(null, null, null, 'text/plain', 100);
        $response->expects($this->once())->method('output');

        $this->getHandler($headers)->send($response, false);
    }

    public function testAttachedNormalResponseWithoutConditionals()
    {
        $headers = $this->getHeaders([200, 'OK'], [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'text/plain',
            'Content-Length' => 100,
            'Content-Disposition' => 'attachment; filename="file.txt"',
            'Cache-Control' => 'private, max-age=0, no-cache, no-store',
            'Pragma' => 'no-cache',
        ]);

        $response = $this->getResponse(null, null, null, 'text/plain', 100, 'file.txt');
        $response->expects($this->once())->method('output');

        $this->getHandler($headers)->send($response, true);
    }

    public function testPartialResponseWithoutConditionals()
    {
        $headers = $this->getHeaders([206, 'Partial Content'], [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'text/plain',
            'Content-Length' => 101,
            'Content-Range' => 'bytes 1100-1200/2000',
            'Cache-Control' => 'private, max-age=0, no-cache, no-store',
            'Pragma' => 'no-cache',
        ], [
            'range' => 'bytes=1100-1200'
        ]);

        $response = $this->getResponse(null, null, null, 'text/plain', 2000);
        $response->expects($this->once())->method('outputBytes');

        $this->getHandler($headers)->send($response);
    }

    public function testPartialResponseFallbackWithoutConditionals()
    {
        $headers = $this->getHeaders([200, 'OK'], [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'text/plain',
            'Content-Length' => 100,
            'Cache-Control' => 'private, max-age=0, no-cache, no-store',
            'Pragma' => 'no-cache',
        ], [
            'range' => 'not valid'
        ]);

        $response = $this->getResponse(null, null, null, 'text/plain', 100);
        $response->expects($this->once())->method('output');

        $this->getHandler($headers)->send($response, false);
    }

    public function testNormalResponse()
    {
        $headers = $this->getHeaders([200, 'OK'], [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'text/plain',
            'Content-Length' => 100,
            'Cache-Control' => 'public, max-age=0, no-cache',
            'Pragma' => 'no-cache',
            'Last-Modified' => 'Thu, 01 Jan 1970 00:00:01 GMT',
        ]);

        $response = $this->getResponse(1, null, null, 'text/plain', 100);
        $response->expects($this->once())->method('output');

        $this->getHandler($headers)->send($response, false);
    }

    public function testAttachedNormalResponse()
    {
        $headers = $this->getHeaders([200, 'OK'], [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'text/plain',
            'Content-Length' => 100,
            'Content-Disposition' => 'attachment; filename="file.txt"',
            'Cache-Control' => 'public, max-age=0, no-cache',
            'Pragma' => 'no-cache',
            'Last-Modified' => 'Thu, 01 Jan 1970 00:00:01 GMT',
        ]);

        $response = $this->getResponse(1, null, null, 'text/plain', 100, 'file.txt');
        $response->expects($this->once())->method('output');

        $this->getHandler($headers)->send($response, true);
    }

    public function testPartialResponse()
    {
        $headers = $this->getHeaders([206, 'Partial Content'], [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'text/plain',
            'Content-Length' => 101,
            'Content-Range' => 'bytes 1100-1200/2000',
            'Cache-Control' => 'public, max-age=0, no-cache',
            'Pragma' => 'no-cache',
            'Last-Modified' => 'Thu, 01 Jan 1970 00:00:01 GMT',
        ], [
            'range' => 'bytes=1100-1200'
        ]);

        $response = $this->getResponse(1, null, null, 'text/plain', 2000);
        $response->expects($this->once())->method('outputBytes');

        $this->getHandler($headers)->send($response, false);
    }

    public function testPartialResponseFallback()
    {
        $headers = $this->getHeaders([200, 'OK'], [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'text/plain',
            'Content-Length' => 100,
            'Cache-Control' => 'public, max-age=0, no-cache',
            'Pragma' => 'no-cache',
            'Last-Modified' => 'Thu, 01 Jan 1970 00:00:01 GMT',
        ], [
            'range' => 'not valid'
        ]);

        $response = $this->getResponse(1, null, null, 'text/plain', 100);
        $response->expects($this->once())->method('output');

        $this->getHandler($headers)->send($response, false);
    }

    public function testNotModified()
    {
        $headers = $this->getHeaders([304, 'Not Modified'], [
            'Cache-Control' => 'public, max-age=0, no-cache',
            'Pragma' => 'no-cache',
            'Last-Modified' => 'Thu, 01 Jan 1970 00:00:01 GMT',
        ], [
            'if-modified-since' => 'Thu, 01 Jan 1970 00:00:01 GMT',
        ]);

        $response = $this->getResponse(1, null, null, 'text/plain', 100);
        $response->expects($this->exactly(0))->method('output');

        $this->getHandler($headers)->send($response, false);
    }

    public function testPreconditionFailed()
    {
        $headers = $this->getHeaders([412, 'Precondition Failed'], [], [
            'if-unmodified-since' => 'Thu, 01 Jan 1970 00:00:00 GMT',
        ]);

        $response = $this->getResponse(1, null, null, 'text/plain', 100);
        $response->expects($this->exactly(0))->method('output');

        $this->getHandler($headers)->send($response, false);
    }

    private function getHandler(Headers $headers)
    {
        return $this->getMock('Riimu\Kit\FileResponse\ResponseHandler', ['disableCompression'], [$headers]);
    }

    protected function getHeaders($expectedStatus = null, array $expectedHeaders = [], array $requestHeaders = [])
    {
        $headers = $this->getMock('Riimu\Kit\FileResponse\Headers', ['setStatus', 'setHeader', 'getHeader', 'headersSent']);
        $headers->expects($this->any())->method('headersSent')->will($this->returnValue(false));

        if ($expectedStatus !== null) {
            $headers->expects($this->once())->method('setStatus')->with($expectedStatus[0], $expectedStatus[1]);
        }

        if ($expectedHeaders !== []) {
            call_user_func_array(
                [$headers->expects($this->exactly(count($expectedHeaders)))->method('setHeader'), 'withConsecutive'],
                array_map(null, array_keys($expectedHeaders), array_values($expectedHeaders))
            );
        } else {
            $headers->expects($this->exactly(0))->method('setHeader');
        }

        $headers->expects($this->any())->method('getHeader')->will($this->returnValueMap(
            array_map(null, array_keys($requestHeaders), array_values($requestHeaders))
        ));

        return $headers;
    }

    protected function getResponse($lastModified, $eTag, $maxAge, $type, $length, $name = null)
    {
        $response = $this->getMock('Riimu\Kit\FileResponse\Response\Response');
        $response->expects($this->any())->method('getLastModified')->will($this->returnValue($lastModified));
        $response->expects($this->any())->method('getETag')->will($this->returnValue($eTag));
        $response->expects($this->any())->method('getMaxAge')->will($this->returnValue($maxAge));
        $response->expects($this->any())->method('getType')->will($this->returnValue($type));
        $response->expects($this->any())->method('getLength')->will($this->returnValue($length));
        $response->expects($this->any())->method('getName')->will($this->returnValue($name));

        return $response;
    }
}
