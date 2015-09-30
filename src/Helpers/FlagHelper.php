<?php
namespace Marchie\LaravelQueueAzureRestarter\Helpers;

use Illuminate\Queue\QueueManager;

class FlagHelper
{
    /**
     * @var QueueManager
     */
    private $queueManager;

    public function __construct(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
    }

    public function getFlagName($connection, $queue)
    {
        return "marchie:laravel-queue-azure-restarter:{$this->getConnectionName($connection)}:{$this->getQueueName($queue)}";
    }

    public function getConnectionName($connection)
    {
        return ($connection !== null) ? $connection : $this->queueManager->getDefaultDriver();
    }

    public function getQueueName($queue)
    {
        return ($queue !== null) ? $queue : 'default';
    }
}