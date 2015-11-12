<?php
namespace Marchie\LaravelQueueAzureRestarter\Console;

use Illuminate\Support\Facades\Config;

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

        return false;
    }
}