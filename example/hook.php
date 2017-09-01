<?php
require 'vendor/autoload.php';

use ZeroRPC\Hook\ConfigMiddleware;
use ZeroRPC\Context;
use ZeroRPC\Client;

/**
 * This client provided a powful configuration to get zerorpc client.
 *
 * Configure use `ZERORPC_` as prefix, that means, if define `ZERORPC_TIME` as key,
 * then you can get the client by `Client("time")`, it's NOT case sensitive.
 *
 * And also support multi-version, for each version of the service, you can either put single
 * endpoint or an array of endpoints.
 */
$middleware = new ConfigMiddleware(array(
    'ZERORPC_TIME' => array(
        '1.0' => 'tcp://127.0.0.1:1234',
        '2.0' => array(
            'tcp://127.0.0.1:2345',
            'tcp://127.0.0.1:1234',
        ),
        'access_key' => 'testing_client_key',
        'default' => '1.0',
    ),
));

$context = new Context();
$context->registerHook('resolve_endpoint', $middleware->resolveEndpoint());
$context->registerHook('before_send_request', $middleware->beforeSendRequest());
$context->registerHook('after_response', function() {
    echo 'Do something after request finished' . PHP_EOL;
});

$client = new Client("time", '1.0', $context);
print $client->time() . PHP_EOL;

$anotherClient = new Client("time", '2.0', $context);
print $anotherClient->time() . PHP_EOL;
