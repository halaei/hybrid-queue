<?php

namespace Halaei\HybridQueue;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Queue\QueueManager;

class HybridJob implements SelfHandling
{
    /**
     * @var mixed the nested job
     */
    protected $job;

    /**
     * @var mixed the nested data
     */
    protected $data;

    /**
     * @var \DateTime
     */
    protected $delay;

    /**
     * @var string short-term queue connection
     */
    protected $connection;

    /**
     * @var string short-term queue name
     */
    protected $queue;

    function __construct($job, $data, $delay, $connection, $queue)
    {
        $this->job = $job;
        $this->data = $data;
        $this->delay = $delay;
        $this->connection = $connection;
        $this->queue = $queue;
    }

    public function handle(QueueManager $queueManager)
    {
        $queueManager->connection($this->connection)->later($this->delay, $this->job, $this->data, $this->queue);
    }
}