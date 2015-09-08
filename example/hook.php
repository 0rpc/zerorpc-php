<?php
require 'vendor/autoload.php';

use ZeroRPC\Hook\ConfigMiddleware;
use ZeroRPC\Context;
use ZeroRPC\Client;

$middleware = new ConfigMiddleware(array(
    'ZERORPC_TIME' => array(
        '1.0' => 'tcp://192.168.222.3:8082',
        'access_key' => 'testing_client_key',
        'default' => '1.0',
    ),
    'ZERORPC_TIME_CLUSTER' => array(
        '1.0' => array(
            'tcp://192.168.222.3:8082',
            'tcp://192.168.222.3:8083',
        ),
        'access_key' => 'testing_client_key',
        'default' => '1.0',
    ),
));

$context = new Context();
$context->registerHook('resolve_endpoint', $middleware->resolveEndpoint());
$context->registerHook('before_send_request', $middleware->beforeSendRequest());

$client = new Client("time", '1.0', $context);
$client->setTimeout(3500);
$client->sleep(2);

$anotherClient = new Client("time_cluster", '1.0', $context);
$anotherClient->setTimeout(3500);
$anotherClient->sleep(2);
