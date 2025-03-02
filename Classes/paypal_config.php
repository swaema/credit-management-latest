<?php

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;


// Set up PayPal API context
$paypal = new ApiContext(
    new OAuthTokenCredential(
        'AUw9bYYJqDrUhcS45wtNNgnCFUJpHiqHqq8O-SUVBqCgr87MDcjOUpMCEKY4k1Pgqy6BLG291BEJn3J3',  // Client ID
        'ELQHSOJ99bSJse8pVsacl4Kx31alKXZ6A_ysyq4Y2rI4rqUWqohtNvI9loJ5_QSW1mMij85gfWSMWHkf'   // Secret
    )
);

$paypal->setConfig([
    'mode' => 'sandbox', // Use 'live' for production
    'http.ConnectionTimeOut' => 30,
    'log.LogEnabled' => true,
    'log.FileName' => 'PayPal.log',
    'log.LogLevel' => 'DEBUG', // Logging level: DEBUG, INFO, WARN, ERROR
    'cache.enabled' => true,
]);

return $paypal;