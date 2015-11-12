<?php
namespace Marchie\LaravelQueueAzureRestarter\Console;

trait PluginEnabledTrait
{
    protected function pluginEnabled()
    {
        $config = $this->app['config'];

        if (($config->get('laravel-queue-azure-restarter.kuduUser', env('KUDU_USER')) !== null)
            && ($config->get('laravel-queue-azure-restarter.kuduPass', env('KUDU_PASS')) !== null)
            && ($config->get('laravel-queue-azure-restarter.azureInstance', env('AZURE_INSTANCE')) !== null)
            && ($config->get('laravel-queue-azure-restarter.queueFailTimeout', env('QUEUE_FAIL_TIMEOUT')) !== null))
        {
            return true;
        }

        return false;
    }
}