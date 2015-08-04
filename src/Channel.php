<?php 
namespace ZeroRPC;

Use ZMQ;

class Channel 
{

    const DEFAULT_TIMEOUT = 600;
    const INTERVAL = 100; 

    private static $pendingRequests = array();
    private static $sockets = array();
    private static $ID = null;

    public static function get($messageID) 
    {
        if (isset(self::$pendingRequests[$messageID])) {
            return self::$pendingRequests[$messageID];
        }
        return false;
    }

    public static function registerSocket($socket)
    {
        array_push(self::$sockets, $socket);
    }

    public static function registerRequest($socket, $event, &$response)
    {
        $messageID = $event->getMessageID();
        self::$pendingRequests[$messageID] = array(
            'socket' => $socket,
            'event' => $event,
            'callback' => function ($content) use (&$response) {
                $response = $content;
            }
        );
    }

    public static function dispatch($timeout = self::DEFAULT_TIMEOUT) 
    {
        $origin_timeout = $timeout;

        $poll = new \ZMQPoll();
        foreach (self::$sockets as $socket) {
            $poll->add($socket, ZMQ::POLL_IN);    
        }

        $read = $write = array();

        while (count(self::$pendingRequests) > 0) {
            $_start = microtime(true);

            $events = $poll->poll($read, $write, self::INTERVAL);
            if ($events > 0) {
                foreach ($read as $socket) {
                    $recv = $socket->recvMulti();
                    $event = Response::deserialize($recv);
                    self::handleEvent($event); 
                }
            }

            $_end = microtime(true);
            $timeout -= ($_end - $_start) * 1000;
            if ($timeout < 0) {
                break;
            }
        }

        if (count(self::$pendingRequests) > 0) {
            $exception = count(self::$pendingRequests) . " requests timeout after {$origin_timeout} ms:\n";
            foreach (self::$pendingRequests as $id => $pending) {
                $exception .= "  # {$pending['event']->name}\n";
            }
            throw new TimeoutException(trim($exception, "\n"));
        }

        $poll->clear();
        self::clear();
    }

    public static function clear() 
    {
        self::$pendingRequests = array();
    }

    public static function startRequest(\ZMQSocket $socket, Event $event, &$response) 
    {
        self::registerRequest($socket, $event, $response);
        $socket->sendMulti($event->serialize());
    }

    public static function handleEvent(Event $event) 
    {
        $messageID = $event->header['response_to'];
        
        if (!isset(self::$pendingRequests[$messageID])) {
            return;
        }

        $pendingRequest = self::$pendingRequests[$messageID];
        $socket = $pendingRequest['socket'];

        if ($event->status === '_zpc_hb') {
            $request = new Request('_zpc_hb', array(), $messageID, $event->getMessageID());
            return $socket->sendMulti($request->serialize());
        }

        $callback = self::$pendingRequests[$messageID]['callback'];
        $callback($event->getContent());
        unset(self::$pendingRequests[$messageID]);
    }

}
