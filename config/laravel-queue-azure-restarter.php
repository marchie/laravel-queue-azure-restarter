<?php

return [
    /**
     * The Azure instance name
     * https://[instance].scm.azurewebsites.net
     */
    'azureInstance' => env('AZURE_INSTANCE', null),

    /**
     * Credentials to log in to kudu
     * See: https://github.com/projectkudu/kudu/wiki/Deployment-credentials
     */
    'kuduUser' => env('KUDU_USER', null),
    'kuduPass' => env('KUDU_PASS', null),

    /**
     * If this time has elapsed since the queue processed the flag job
     * then the queue has failed and will be restarted
     */
    'queueFailTimeout' => env('QUEUE_FAIL_TIMEOUT', null)
];
