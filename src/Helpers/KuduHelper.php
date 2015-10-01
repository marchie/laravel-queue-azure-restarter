<?php
namespace Marchie\LaravelQueueAzureRestarter\Helpers;

use GuzzleHttp\Client;

class KuduHelper
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function killQueueWorkers($connection = null, $queue = null)
    {
        $processes = $this->getProcesses();

        $killedProcesses = 0;

        foreach ($processes as $process) {
            if ($this->isWorkerProcess($process, $connection, $queue)) {
                if ($this->killProcess($process->id)) {
                    $killedProcesses++;
                }
            }
        }

        return $killedProcesses;
    }

    public function testConnection()
    {
        return ($this->makeRequest('GET', 'api/processes')->getStatusCode() < 400);
    }

    private function getProcesses()
    {
        return json_decode($this->makeRequest('GET', 'api/processes')->getBody());
    }

    private function getProcess($pid)
    {
        return json_decode($this->makeRequest('GET', 'api/processes/' . $pid)->getBody());
    }

    private function killProcess($pid)
    {
        return ($this->makeRequest('DELETE', 'api/processes/' . $pid)->getStatusCode() < 400);
    }

    private function makeRequest($method, $uri)
    {
        return $this->client->request($method, 'https://' . config('laravel-queue-azure-restarter.scm') . '.scm.azurewebsites.net/' . $uri, [
            'auth' => [
                config('laravel-queue-azure-restarter.kuduUser', env('KUDU_USER')),
                config('laravel-queue-azure-restarter.kuduPass', env('KUDU_PASS'))
            ]
        ]);
    }

    private function isWorkerProcess($process, $connection = null, $queue = null)
    {
        if (strpos('php', $process->name) === 0) {
            $info = $this->getProcess($process->id);

            if (($info->is_webjob === true)
                && (strpos($info->command_line, 'queue:work') !== false)
            ) {
                if (isset($connection)
                    && (preg_match('/\s' . $connection . '(\s|$)/', $info->command_line) === 0)
                ) {
                    return false;
                }

                if (isset($queue)
                    && (strpos($info->command_line, '--queue="' . $queue . '"') === false)
                ) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }
}