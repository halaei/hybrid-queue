<?php

namespace Halaei\HybridQueue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use LogicException;

class HybridQueue extends Queue implements QueueContract
{
    /**
     * @var QueueContract
     */
    protected $shortTermQueue;

    /**
     * @var QueueContract
     */
    protected $longTermQueue;

    /**
     * @var string the name of short term queue
     */
    protected $shortTermConnection;

    /**
     * @var int hours
     */
    protected $threshold;

    /**
     * @param QueueContract $shortTermQueue
     * @param QueueContract $longTermQueue
     * @param string        $shortTermConnection
     * @param int           $threshold
     */
    public function __construct(QueueContract $shortTermQueue,QueueContract $longTermQueue, $shortTermConnection, $threshold)
    {
        $this->shortTermQueue = $shortTermQueue;
        $this->longTermQueue = $longTermQueue;
        $this->shortTermConnection = $shortTermConnection;
        $this->threshold = $threshold;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->shortTermQueue->push($job, $data, $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->shortTermQueue->pushRaw($payload, $queue, $options);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($this->isLongTerm($delay)) {
            return $this->longTermQueue->later($this->longTermDelay($delay),
                new HybridJob($job, $data, $this->shortTermDelay($delay), $this->shortTermConnection, $queue), '', $queue);
        }
        return $this->shortTermQueue->later($delay, $job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        throw new LogicException('Pop directly from short-term or long-term queues instead of a hybrid one.');
    }

    /**
     * @param $delay
     * @return bool
     */
    protected function isLongTerm($delay)
    {
        return $this->getSeconds($delay) > $this->threshold * 3600;
    }

    /**
     * @param $delay
     * @return mixed
     */
    protected function longTermDelay($delay)
    {
        return $this->getSeconds($delay) - ($this->threshold * 3600) / 2;
    }

    public function shortTermDelay($delay)
    {
        if($delay instanceof \DateTime) {
            return $delay;
        }
        $seconds = (int) $delay;
        return new \DateTime("+$seconds seconds");
    }
}