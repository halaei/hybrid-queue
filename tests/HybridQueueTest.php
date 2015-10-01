<?php

use Halaei\HybridQueue\HybridJob;
use Halaei\HybridQueue\HybridQueue;
use Illuminate\Queue\RedisQueue;
use Mockery\MockInterface;

class HybridQueueTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var RedisQueue|MockInterface
     */
    private $shortTermQueue;

    /**
     * @var RedisQueue|MockInterface
     */
    private $longTermQueue;

    /**
     * @var HybridQueue
     */
    private $hybridQueue;

    protected function setUp()
    {
        $this->shortTermQueue = Mockery::mock(RedisQueue::class);
        $this->longTermQueue = Mockery::mock(RedisQueue::class);
        $this->hybridQueue = new HybridQueue($this->shortTermQueue, $this->longTermQueue, 'redis', 24);
        parent::setUp();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testPush()
    {
        $this->shortTermQueue->shouldReceive('push')->once()->with('some job', 'some data', 'some queue');
        $this->hybridQueue->push('some job', 'some data', 'some queue');
    }

    public function testPushRaw()
    {
        $this->shortTermQueue->shouldReceive('pushRaw')->once()->with('some payload', 'some queue', ['some options']);
        $this->hybridQueue->pushRaw('some payload', 'some queue', ['some options']);
    }

    public function testLaterOnLongTermQueue()
    {
        $delay = new DateTime('+25 Hours');
        $approximateSeconds = 3600 * 25 - 3600 * 24 / 2;
        $this->longTermQueue->shouldReceive('later')->once()->with(
            Mockery::on(function($seconds) use ($approximateSeconds) {
                $this->assertGreaterThanOrEqual($approximateSeconds, $seconds + 10);
                $this->assertLessThanOrEqual($approximateSeconds, $seconds);
                return true;
            }),
            Mockery::on(function(HybridJob $job) use ($delay){
                $this->assertEquals('job', $job->getJob());
                $this->assertEquals('data', $job->getData());
                $this->assertSame($delay, $job->getDelay());
                $this->assertEquals('redis', $job->getConnection());
                $this->assertEquals(null, $job->getQueue());
                return true;
            })
            , '', null);
        $this->hybridQueue->later($delay, 'job', 'data');
    }

    public function testLaterOnShortTermQueue()
    {
        $this->shortTermQueue->shouldReceive('later')->once()->with(60, 'job', 'data', 'queue');
        $this->hybridQueue->later(60, 'job', 'data', 'queue');
    }
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Pop directly from short-term or long-term queues instead of a hybrid one.
     */
    public function testPop()
    {
        $this->hybridQueue->pop();
    }
}