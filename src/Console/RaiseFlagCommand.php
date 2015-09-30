<?php
namespace Marchie\LaravelQueueAzureRestarter\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\QueueManager;
use Marchie\LaravelQueueAzureRestarter\Jobs\RaiseFlagJob;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RaiseFlagCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:flag';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a flag job to the queue';
    /**
     * @var
     */

    private $queueManager;

    public function __construct(QueueManager $queueManager)
    {
        parent::__construct();

        $this->queueManager = $queueManager;
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

        $job = (new RaiseFlagJob($connection, $queue))->onQueue($queue);

        $this->queueManager->connection($connection)->push($job);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of connection', $this->queueManager->getDefaultDriver()],
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
}