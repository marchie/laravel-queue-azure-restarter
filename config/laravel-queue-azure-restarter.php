<?php

return [
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
    'timeout' => env('QUEUE_FAIL_TIMEOUT', null)
];
