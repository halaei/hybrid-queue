<?php

use Halaei\HybridQueue\HybridQueue;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\RedisQueue;

class HybridQueueConnectorTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testConnector()
    {
        $manager = Mockery::mock(QueueManager::class);
        $config = [
            'short-term-queue' => 'a-redis-queue-connection',
            'long-term-queue' => 'a-database-queue-connection',
            'threshold' => 24,
        ];

        $shortTermQueue = Mockery::mock(RedisQueue::class);
        $longTermQueue = Mockery::mock(RedisQueue::class);

        $manager->shouldReceive('connection')->once()->with('a-redis-queue-connection')->andReturn($shortTermQueue);
        $manager->shouldReceive('connection')->once()->with('a-database-queue-connection')->andReturn($longTermQueue);

        $connector = new \Halaei\HybridQueue\HybridQueueConnector($manager);
        $queue = $connector->connect($config);

        $this->assertInstanceOf(HybridQueue::class, $queue);

        $shortTermQueue->shouldReceive('push')->once();
        $queue->push('a short term job');

        $longTermQueue->shouldReceive('later')->once();
        $queue->later(new DateTime('+2 Days'), 'a long term job');
    }
}