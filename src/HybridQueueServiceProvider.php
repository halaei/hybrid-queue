<?php

namespace Halaei\HybridQueue;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

class HybridQueueServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot(QueueManager $queueManager)
    {
        $queueManager->addConnector('hybrid', function() use ($queueManager) {
            return new HybridQueueConnector($queueManager);
        });
    }
}