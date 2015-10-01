<?php

namespace Halaei\HybridQueue;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\QueueManager;

class HybridQueueConnector implements ConnectorInterface
{
    /**
     * @var QueueManager
     */
    protected $queueManager;

    function __construct(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $shortTermQueue = $this->queueManager->connection($config['short-term-queue']);
        $longTermQueue = $this->queueManager->connection($config['long-term-queue']);
        return new HybridQueue($shortTermQueue, $longTermQueue, $config['short-term-queue'], $config['threshold']);
    }
}