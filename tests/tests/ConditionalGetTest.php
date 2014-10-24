<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ConditionalGetTest extends \PHPUnit_Framework_TestCase
{
    public function testRangeMatchMissingArguments()
    {
        $this->setExpectedException('InvalidArgumentException');
        (new ConditionalGet())->matchRange(null, null);
    }

    public function testMissingRangeHeaders()
    {
        $this->assertFalse(
            (new ConditionalGet($this->getHeaders(['range' => ''])))->matchRange(null, 'foo')
        );
        $this->assertFalse(
            (new ConditionalGet($this->getHeaders(['if-range' => ''])))->matchRange(null, 'foo')
        );
    }

    public function testRangeTag()
    {
        $this->assertTrue(
            (new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => '"foo"'])))->matchRange(null, 'foo')
        );
        $this->assertFalse(
            (new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => '"bar"'])))->matchRange(null, 'foo')
        );
        $this->assertFalse(
            (new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => 'W/"foo"'])))->matchRange(null, 'foo')
        );
    }

    public function testRangeAllowedDates()
    {
        $time = 'Sun, 06 Nov 1994 08:49:37 GMT';
        $cond = new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => $time]));
        $this->assertTrue($cond->matchRange(strtotime($time), null));
        $this->assertTrue($cond->matchRange('' . strtotime($time), null));
        $this->assertTrue($cond->matchRange(new \DateTime($time), null));

        if (interface_exists('DateTimeInterface')) {
            $this->assertTrue($cond->matchRange(new \DateTimeImmutable($time), null));
        }
    }

    public function testInvalidTimeStamp()
    {
        $this->setExpectedException('InvalidArgumentException');
        (new ConditionalGet($this->getHeaders(['range' => '', 'if-range' => 'Sun, 06 Nov 1994 08:49:37 GMT'])))->matchRange('foo', null);
    }

    private function getHeaders(array $headers = [])
    {
        $mock = $this->getMock('Riimu\Kit\FileResponse\Headers', ['loadHeaders']);
        $prop = new \ReflectionProperty('Riimu\Kit\FileResponse\Headers', 'headers');
        $prop->setAccessible(true);
        $prop->setValue($mock, $headers);
        return $mock;
    }
}
