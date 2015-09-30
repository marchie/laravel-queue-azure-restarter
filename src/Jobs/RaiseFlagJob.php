<?php
namespace Marchie\LaravelQueueAzureRestarter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Marchie\LaravelQueueAzureRestarter\Helpers\FlagHelper;

class RaiseFlagJob implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable;
    /**
     * @var
     */
    private $connectionName;
    /**
     * @var
     */
    private $queueName;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($connectionName, $queueName)
    {
        $this->connectionName = $connectionName;
        $this->queueName = $queueName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Cache $cache, FlagHelper $flag)
    {
        $cache->forever($flag->getFlagName($this->connectionName, $this->queueName), time());
    }
}