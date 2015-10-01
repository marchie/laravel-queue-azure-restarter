<?php
namespace Marchie\LaravelQueueAzureRestarter\Console;

use Illuminate\Console\Command;
use Marchie\LaravelQueueAzureRestarter\Helpers\KuduHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class TestKuduCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'kudu:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connectivity to Azure SCM';
    /**
     * @var KuduHelper
     */
    private $kuduHelper;

    /**
     * @var
     */

    public function __construct(KuduHelper $kuduHelper)
    {
        $this->kuduHelper = $kuduHelper;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->kuduHelper->testConnection())
        {
            $this->info('Connection test to Kudu was successful!');
        }
    }
}