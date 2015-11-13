<?php
namespace Marchie\LaravelQueueAzureRestarter\Console;

use Illuminate\Support\Facades\Config;
use Marchie\LaravelQueueAzureRestarter\Exceptions\PluginNotEnabledException;

trait PluginEnabledTrait
{
    protected function pluginEnabled()
    {
        if ((Config::get('laravel-queue-azure-restarter.kuduUser', env('KUDU_USER')) !== null)
            && (Config::get('laravel-queue-azure-restarter.kuduPass', env('KUDU_PASS')) !== null)
            && (Config::get('laravel-queue-azure-restarter.azureInstance', env('AZURE_INSTANCE')) !== null)
            && (Config::get('laravel-queue-azure-restarter.queueFailTimeout', env('QUEUE_FAIL_TIMEOUT')) !== null))
        {
            return true;
        }

        throw new PluginNotEnabledException('The plugin is not enabled - please check your .env settings');
    }
}