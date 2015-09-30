<?php namespace Marchie\LaravelQueueAzureRestarter;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Marchie\LaravelQueueAzureRestarter\Console\RaiseFlagCommand;
use Marchie\LaravelQueueAzureRestarter\Console\SaluteFlagCommand;
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
        if ($this->pluginEnabled())
        {
            $configPath = __DIR__ . '/../config/laravel-queue-azure-restarter.php';
            $this->mergeConfigFrom($configPath, 'laravel-queue-azure-restarter');
            $this->registerHelpers();
            $this->registerCommands();
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        if ($this->pluginEnabled())
        {
            return [
                'command.queue.flag',
                'command.queue.check',
                'Marchie\LaravelQueueAzureRestarter\Helpers\FlagHelper',
                'Marchie\LaravelQueueAzureRestarter\Helpers\KuduHelper'
            ];
        }

        return [];
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
                return new RaiseFlagCommand(
                    $app['queue']
                );
            }
        );

        $this->app['command.queue.check'] = $this->app->share(
            function ($app) {
                return new SaluteFlagCommand(
                    $app['Illuminate\Contracts\Cache\Repository'],
                    $app['Carbon\Carbon'],
                    $app['log'],
                    $app['Marchie\LaravelAzureQueueRestarter\FlagHelper'],
                    $app['Marchie\LaravelAzureQueueRestarter\KuduHelper']
                );
            }
        );

        $this->commands('command.queue.flag', 'command.queue.check');
    }

    private function pluginEnabled()
    {
        $config = $this->app['config'];

        if (($config->get('laravel-queue-azure-restarter.kuduUser') !== null)
            && ($config->get('laravel-queue-azure-restarter.kuduPass') !== null)
            && ($config->get('laravel-queue-azure-restarter.scm') !== null)
            && ($config->get('laravel-queue-azure-restarter.timeout') !== null))
        {
            return true;
        }

        return false;
    }
}
