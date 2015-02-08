<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ConditionalGetTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultOkStatus()
    {
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders()->getResponseStatus(null, null));
    }

    public function testDefaultRangeStatus()
    {
        $this->assertSame(ConditionalGet::HTTP_PARTIAL_CONTENT, $this->getWithHeaders([
            'range' => '',
        ])->getResponseStatus(null, null));
    }

    public function testOkWithNoConditionals()
    {
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-none-match' => '"foo"',
        ])->getResponseStatus(null, null));
    }

    public function testNotModified()
    {
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-none-match' => '"foo"',
        ])->getResponseStatus(null, 'foo'));
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-none-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-none-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));
    }

    public function testOkWhenModified()
    {
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:39 GMT'), null));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-none-match' => '"foo"',
        ])->getResponseStatus(null, 'bar'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-none-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:39 GMT'), 'foo'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-none-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'bar'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-none-match' => '"foo"',
        ])->getResponseStatus(null, 'bar'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-none-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
    }

    public function testPreconditionOk()
    {
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-match' => '"foo"',
        ])->getResponseStatus(null, 'foo'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));
    }

    public function testPreconditionFailed()
    {
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, $this->getWithHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:39 GMT'), null));
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, $this->getWithHeaders([
            'if-match' => '"foo"',
        ])->getResponseStatus(null, 'bar'));
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, $this->getWithHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:39 GMT'), 'foo'));
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, $this->getWithHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'bar'));
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, $this->getWithHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-match' => '"foo"',
        ])->getResponseStatus(null, 'bar'));
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, $this->getWithHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'if-match' => '"foo"',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
    }

    public function testPartialIfRange()
    {
        $this->assertSame(ConditionalGet::HTTP_PARTIAL_CONTENT, $this->getWithHeaders([
            'if-range' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'range' => '',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
        $this->assertSame(ConditionalGet::HTTP_PARTIAL_CONTENT, $this->getWithHeaders([
            'if-range' => '"foo"',
            'range' => '',
        ])->getResponseStatus(null, 'foo'));
    }

    public function testNoPartialIfRange()
    {
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-range' => 'Sun, 06 Nov 1994 08:49:38 GMT',
            'range' => '',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:39 GMT'), null));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-range' => '"foo"',
            'range' => '',
        ])->getResponseStatus(null, 'bar'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-range' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])->getResponseStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
    }

    public function testDateTimeAsTimestamp()
    {
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])->getResponseStatus(new \DateTime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
    }

    public function testIgnoreWeakTags()
    {
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-none-match' => 'W/"foo"',
        ])->getResponseStatus(null, 'foo'));
    }

    public function testMultipleTags()
    {
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-none-match' => '"foo", "bar", "baz"',
        ])->getResponseStatus(null, 'foo'));
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-none-match' => '"foo", "bar", "baz"',
        ])->getResponseStatus(null, 'bar'));
        $this->assertSame(ConditionalGet::HTTP_OK, $this->getWithHeaders([
            'if-none-match' => '"foo", "bar", W/"baz"',
        ])->getResponseStatus(null, 'baz'));
    }

    public function testMatchAllTag()
    {
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, $this->getWithHeaders([
            'if-none-match' => '*',
        ])->getResponseStatus(null, 'foo'));
    }

    public function testHeaderConflictWithMatch()
    {
        $this->setExpectedException('Riimu\Kit\FileResponse\UndefinedResultException');
        $this->getWithHeaders([
            'if-match' => '',
            'if-none-match' => '',
        ])->getResponseStatus(null, null);
    }

    public function testHeaderConflictWithModified()
    {
        $this->setExpectedException('Riimu\Kit\FileResponse\UndefinedResultException');
        $this->getWithHeaders([
            'if-modified-since' => '',
            'if-unmodified-since' => '',
        ])->getResponseStatus(null, null);
    }

    private function getWithHeaders(array $headers = [], $method = 'GET')
    {
        return new ConditionalGet($this->getHeaders($headers, $method));
    }

    private function getHeaders(array $headers = [], $method = null)
    {
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders', 'getRequestMethod']);
        $prop = new \ReflectionProperty('Riimu\Kit\FileResponse\Headers', 'headers');
        $prop->setAccessible(true);
        $prop->setValue($mock, $headers);
        $mock->expects($this->any())->method('getRequestMethod')->will($this->returnValue($method));
        return $mock;
    }
}
