<?php
namespace Marchie\LaravelQueueAzureRestarter\Helpers;

use GuzzleHttp\Client;
use Marchie\LaravelQueueAzureRestarter\Exceptions\RequestFailedException;

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

        foreach ($processes as $process)
        {
            if ($this->isWorkerProcess($process, $connection, $queue))
            {
                $this->killProcess($process->id);

                $killedProcesses++;
            }
        }

        return $killedProcesses;
    }

    private function getProcesses()
    {
        return json_decode($this->makeRequest('GET', 'api/processes'));
    }

    private function getProcess($pid)
    {
        return json_decode($this->makeRequest('GET', 'api/processes/' . $pid));
    }

    private function killProcess($pid)
    {
        return $this->makeRequest('DELETE', 'api/processes/' . $pid);
    }

    private function makeRequest($method, $uri)
    {
        $request = $this->client->request($method, 'https://' . config('laravel-queue-azure-restarter.scm') . '/' . $uri, [
            'auth' => [
                config('laravel-queue-azure-restarter.kuduUser'),
                config('laravel-queue-azure-restarter.kuduPass')
            ]
        ]);

        if ($request->getStatusCode() < 400)
        {
            return $request->getBody();
        }

        throw new RequestFailedException('"' . $method . '" request to "https://' . config('laravel-queue-azure-restarter.scm') . '/' . $uri . '" failed');
    }

    private function isWorkerProcess($process, $connection = null, $queue = null)
    {
        if (strpos('php', $process->name) === 0) {
            $info = $this->getProcess($process->id);

            if (($info->is_webjob === true)
                && (strpos($info->command_line, 'queue:work') !== false)
            ) {
                if (isset($connection)
                    && (preg_match('/\s' . $connection . '(/s|$)/', $info->command_line) === 0)
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