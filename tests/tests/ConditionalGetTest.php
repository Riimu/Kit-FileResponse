<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ConditionalGetTest extends \PHPUnit_Framework_TestCase
{
    public function testStatusCodesWithTag()
    {
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, (new ConditionalGet($this->getHeaders([
            'if-none-match' => '"foo"',
        ], 'GET')))->checkStatus(null, 'foo'));
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, (new ConditionalGet($this->getHeaders([
            'if-none-match' => '"foo"',
        ], 'POST')))->checkStatus(null, 'foo'));
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, (new ConditionalGet($this->getHeaders([
            'if-match' => '"foo"',
        ])))->checkStatus(null, 'bar'));
        $this->assertSame(ConditionalGet::HTTP_OK, (new ConditionalGet($this->getHeaders([
            'if-range' => '"foo"',
        ])))->checkStatus(null, 'foo'));
        $this->assertSame(ConditionalGet::HTTP_PARTIAL_CONTENT, (new ConditionalGet($this->getHeaders([
            'range' => '',
        ])))->checkStatus(null, 'foo'));
        $this->assertSame(ConditionalGet::HTTP_OK, (new ConditionalGet($this->getHeaders([])))->checkStatus(null, 'bar'));
    }

    public function testStatusCodesWithDate()
    {
        $this->assertSame(ConditionalGet::HTTP_NOT_MODIFIED, (new ConditionalGet($this->getHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
        ])))->checkStatus(strtotime('Sun, 06 Nov 1994 08:49:37 GMT'), null));
        $this->assertSame(ConditionalGet::HTTP_PRECONDITION_FAILED, (new ConditionalGet($this->getHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
        ])))->checkStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
        $this->assertSame(ConditionalGet::HTTP_OK, (new ConditionalGet($this->getHeaders([
            'if-range' => 'Sun, 06 Nov 1994 08:49:38 GMT',
        ])))->checkStatus(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), null));
    }

    public function testCacheMissingArguments()
    {
        $this->setExpectedException('InvalidArgumentException');
        (new ConditionalGet())->checkCache(null, null);
    }

    public function testMissingConditionalHeaders()
    {
        $this->assertFalse((new ConditionalGet($this->getHeaders([])))->checkCache(null, 'foo'));
        $this->assertTrue((new ConditionalGet($this->getHeaders([])))->checkConditions(null, 'foo'));
    }

    public function testUnconventionalTags()
    {
        $this->assertTrue((new ConditionalGet($this->getHeaders(['if-match' => '*'])))->checkConditions(null, 'foo'));
        $this->assertFalse((new ConditionalGet($this->getHeaders(['if-match' => ''])))->checkConditions(null, 'foo'));
    }

    public function testSuccessConditions()
    {
        $this->assertTrue((new ConditionalGet($this->getHeaders(['if-match' => '"foo"'])))->checkConditions(null, 'foo'));
        $this->assertTrue((new ConditionalGet($this->getHeaders(['if-match' => '"bar", "foo"'])))->checkConditions(null, 'foo'));
        $this->assertTrue((new ConditionalGet(
            $this->getHeaders(['if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT'])
        ))->checkConditions(strtotime('Sun, 06 Nov 1994 08:49:37 GMT'), 'foo'));

        $this->assertTrue((new ConditionalGet($this->getHeaders(['if-none-match' => '"foo"'])))->checkCache(null, 'foo'));
        $this->assertTrue((new ConditionalGet($this->getHeaders(['if-none-match' => '"bar", "foo"'])))->checkCache(null, 'foo'));
        $this->assertTrue((new ConditionalGet(
            $this->getHeaders(['if-modified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT'])
        ))->checkCache(strtotime('Sun, 06 Nov 1994 08:49:37 GMT'), 'foo'));
    }

    public function testFailingConditions()
    {
        $this->assertFalse((new ConditionalGet($this->getHeaders(['if-match' => '"foo"'])))->checkConditions(null, 'bar'));
        $this->assertFalse((new ConditionalGet($this->getHeaders(['if-match' => '"bar", "foo"'])))->checkConditions(null, 'baz'));
        $this->assertFalse((new ConditionalGet(
            $this->getHeaders(['if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT'])
        ))->checkConditions(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));

        $this->assertFalse((new ConditionalGet($this->getHeaders(['if-none-match' => '"foo"'])))->checkCache(null, 'bar'));
        $this->assertFalse((new ConditionalGet($this->getHeaders(['if-none-match' => '"bar", "foo"'])))->checkCache(null, 'baz'));
        $this->assertFalse((new ConditionalGet(
            $this->getHeaders(['if-modified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT'])
        ))->checkCache(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));
    }

    public function testDoubleSuccess()
    {
        $this->assertTrue((new ConditionalGet($this->getHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'if-match' => '"foo"',
        ])))->checkConditions(strtotime('Sun, 06 Nov 1994 08:49:37 GMT'), 'foo'));
        $this->assertTrue((new ConditionalGet($this->getHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'if-none-match' => '"foo"',
        ])))->checkCache(strtotime('Sun, 06 Nov 1994 08:49:37 GMT'), 'foo'));
    }

    public function testCrossFailures()
    {
        $this->assertFalse((new ConditionalGet($this->getHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'if-match' => '"foo"',
        ])))->checkConditions(strtotime('Sun, 06 Nov 1994 08:49:37 GMT'), 'bar'));
        $this->assertFalse((new ConditionalGet($this->getHeaders([
            'if-unmodified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'if-match' => '"foo"',
        ])))->checkConditions(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));

        $this->assertFalse((new ConditionalGet($this->getHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'if-none-match' => '"foo"',
        ])))->checkCache(strtotime('Sun, 06 Nov 1994 08:49:37 GMT'), 'bar'));
        $this->assertFalse((new ConditionalGet($this->getHeaders([
            'if-modified-since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'if-none-match' => '"foo"',
        ])))->checkCache(strtotime('Sun, 06 Nov 1994 08:49:38 GMT'), 'foo'));
    }

    public function testUndefinedResult()
    {
        $this->setExpectedException('Riimu\Kit\FileResponse\ResultUndefinedException');
        $this->assertFalse((new ConditionalGet(
            $this->getHeaders(['if-match' => '', 'if-none-match' => ''])
        ))->checkCache(null, 'foo'));
    }

    public function testRangeMatchMissingArguments()
    {
        $this->setExpectedException('InvalidArgumentException');
        (new ConditionalGet())->checkRange(null, null);
    }

    public function testMissingRangeHeaders()
    {
        $this->assertFalse((new ConditionalGet($this->getHeaders([])))->checkRange(null, 'foo'));
        $this->assertFalse((new ConditionalGet($this->getHeaders(['range' => ''])))->checkRange(null, 'foo'));
        $this->assertFalse((new ConditionalGet($this->getHeaders(['if-range' => ''])))->checkRange(null, 'foo'));
    }

    public function testRangeTag()
    {
        $this->assertTrue(
            (new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => '"foo"'])))->checkRange(null, 'foo')
        );
        $this->assertFalse(
            (new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => '"bar"'])))->checkRange(null, 'foo')
        );
        $this->assertFalse(
            (new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => 'W/"foo"'])))->checkRange(null, 'foo')
        );
    }

    public function testRangeAllowedDates()
    {
        $time = 'Sun, 06 Nov 1994 08:49:37 GMT';
        $cond = new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => $time]));
        $this->assertTrue($cond->checkRange(strtotime($time), null));
        $this->assertTrue($cond->checkRange('' . strtotime($time), null));
        $this->assertTrue($cond->checkRange(new \DateTime($time), null));

        if (interface_exists('DateTimeInterface')) {
            $this->assertTrue($cond->checkRange(new \DateTimeImmutable($time), null));
        }
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
