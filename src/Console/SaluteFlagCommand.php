<?php
namespace Marchie\LaravelQueueAzureRestarter\Console;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Console\Command;
use Illuminate\Contracts\Logging\Log;
use Marchie\LaravelQueueAzureRestarter\Exceptions\QueueProcessesNotKilledException;
use Marchie\LaravelQueueAzureRestarter\Exceptions\UnresponsiveQueueWorkerException;
use Marchie\LaravelQueueAzureRestarter\Helpers\FlagHelper;
use Marchie\LaravelQueueAzureRestarter\Helpers\KuduHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SaluteFlagCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the queue worker has not turned into a zombie';

    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var Carbon
     */
    private $carbon;
    /**
     * @var Log
     */
    private $log;
    /**
     * @var FlagHelper
     */
    private $flagHelper;
    /**
     * @var KuduHelper
     */
    private $kuduHelper;


    public function __construct(Cache $cache, Carbon $carbon, Log $log, FlagHelper $flagHelper, KuduHelper $kuduHelper)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->carbon = $carbon;
        $this->log = $log;
        $this->flagHelper = $flagHelper;
        $this->kuduHelper = $kuduHelper;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $queue = $this->option('queue');

        $connection = $this->argument('connection');

        $flag = $this->cache->get($this->flagHelper->getFlagName($connection, $queue), 0);

        if ($this->flagIsDown($flag))
        {
            $killedWorkers = $this->kuduHelper->killQueueWorkers($connection, $queue);

            if ($killedWorkers === 0) {
                throw new QueueProcessesNotKilledException('The "' . $this->flagHelper->getQueueName() . '" queue on connection "' . $this->flagHelper->getConnectionName() . '" is unresponsive, but the process could not be terminated.');
            }

            $infoString = 'The "' . $this->flagHelper->getQueueName() . '" queue on connection "' . $this->flagHelper->getConnectionName() . '" was unresponsive. ' . $killedWorkers . ' process';

            if ($killedWorkers > 1)
            {
                $infoString .= 'es';
            }

            $infoString .= ' were terminated.';

            $this->info($infoString);

            throw new UnresponsiveQueueWorkerException($infoString);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of connection', null],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on']
        ];
    }

    private function flagIsDown($flag)
    {
        $lastRaised = $this->carbon->createFromTimestamp($flag);

        $threshold = $this->carbon->parse('-' . config('laravel-queue-azure-restarter.timeout'));

        return $lastRaised->lt($threshold);
    }
}