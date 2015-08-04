<?php
require 'vendor/autoload.php';

use ZeroRPC\Client;
use ZeroRPC\Channel;

$client = new Client("tcp://127.0.0.1:1234");

try {
    $client->should_response_error();
} catch (ZeroRPC\RemoteException $e) {
    print $e;
} 

try {
    $client->sleep(3);
} catch (ZeroRPC\TimeoutException $e) {
    print $e;
}

try {
    $client->async("sleep", array(1), $null_);
    $client->async("sleep", array(2), $null_);
    Channel::dispatch();
} catch (ZeroRPC\TimeoutException $e) {
    print $e;
}




