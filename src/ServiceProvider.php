<?php namespace Marchie\LaravelQueueAzureRestarter;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Marchie\LaravelQueueAzureRestarter\Console\EnqueueFlagCommand;
use Marchie\LaravelQueueAzureRestarter\Console\CheckQueueCommand;
use Marchie\LaravelQueueAzureRestarter\Console\TestKuduCommand;
use Marchie\LaravelQueueAzureRestarter\Helpers\FlagHelper;
use Marchie\LaravelQueueAzureRestarter\Helpers\KuduHelper;

class ServiceProvider extends LaravelServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {
        $configPath = __DIR__ . '/../config/laravel-queue-azure-restarter.php';

        $this->publishes([$configPath => config_path('laravel-queue-azure-restarter.php')]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $configPath = __DIR__ . '/../config/laravel-queue-azure-restarter.php';
        $this->mergeConfigFrom($configPath, 'laravel-queue-azure-restarter');
        $this->registerHelpers();
        $this->registerCommands();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.queue.flag',
            'command.queue.check',
            'command.kudu.test',
            'Marchie\LaravelQueueAzureRestarter\Helpers\FlagHelper',
            'Marchie\LaravelQueueAzureRestarter\Helpers\KuduHelper'
        ];
    }

    private function registerHelpers()
    {
        $this->app->bind('Marchie\LaravelAzureQueueRestarter\FlagHelper', function ($app) {
            return new FlagHelper(
                $app->make('Illuminate\Queue\QueueManager')
            );
        });

        $this->app->bind('Marchie\LaravelAzureQueueRestarter\KuduHelper', function ($app) {
            return new KuduHelper(
                $app->make('GuzzleHttp\Client')
            );
        });
    }

    private function registerCommands()
    {
        $this->app['command.queue.flag'] = $this->app->share(
            function ($app) {
                return new EnqueueFlagCommand(
                    $app['queue']
                );
            }
        );

        $this->app['command.queue.check'] = $this->app->share(
            function ($app) {
                return new CheckQueueCommand(
                    $app['Illuminate\Contracts\Cache\Repository'],
                    $app['Carbon\Carbon'],
                    $app['log'],
                    $app['Marchie\LaravelAzureQueueRestarter\FlagHelper'],
                    $app['Marchie\LaravelAzureQueueRestarter\KuduHelper'],
                    $app['queue']
                );
            }
        );

        $this->app['command.kudu.test'] = $this->app->share(
            function ($app) {
                return new TestKuduCommand(
                    $app['Marchie\LaravelAzureQueueRestarter\KuduHelper']
                );
            }
        );

        $this->commands('command.queue.flag', 'command.queue.check', 'command.kudu.test');
    }
}
