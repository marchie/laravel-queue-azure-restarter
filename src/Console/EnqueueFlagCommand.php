<?php
namespace Marchie\LaravelQueueAzureRestarter\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\QueueManager;
use Marchie\LaravelQueueAzureRestarter\Exceptions\PluginNotEnabledException;
use Marchie\LaravelQueueAzureRestarter\Jobs\RaiseFlagJob;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class EnqueueFlagCommand extends Command
{
    use PluginEnabledTrait;

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
        $this->queueManager = $queueManager;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->pluginEnabled()) {
            $queue = $this->option('queue');

            $connection = $this->argument('connection');

            $job = (new RaiseFlagJob($connection, $queue))->onQueue($queue);

            $this->queueManager->connection($connection)->push($job);

            return;
        }

        throw new PluginNotEnabledException('The plugin is not enabled - please check your .env settings');
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