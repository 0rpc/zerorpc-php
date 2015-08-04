<?php
require 'vendor/autoload.php';

use ZeroRPC\Client;
use ZeroRPC\Channel;

$clientA = new Client("tcp://127.0.0.1:1234");
$clientA->setTimeout(3500);
$clientB = new Client("tcp://127.0.0.1:2345");
$clientB->setTimeout(3500);

// normal example
$time = $clientA->strftime("%Y/%m/%d %H:%M:%S");
$clientA->async("strftime", array("%Y/%m/%d %H:%M:%S"), $async_time);
Channel::dispatch();
assert($time == $async_time);
print "Time is $async_time!" . PHP_EOL;


// sync example
print "Example 1: start sync call:" . PHP_EOL;
$start = microtime(true);

$clientA->sleep(3);
$clientB->sleep(2);

print 'cost ' . (microtime(true) - $start) . ' s'.PHP_EOL;


// async example
print "Example 2: start async call:" . PHP_EOL;
$start = microtime(true);

$clientA->async("sleep", array(3), $sleep1);
$clientB->async("sleep", array(2), $sleep2);
Channel::dispatch(3500);

print 'cost ' . (microtime(true) - $start) . ' s'.PHP_EOL;


