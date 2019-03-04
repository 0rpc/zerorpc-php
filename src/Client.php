<?php
namespace ZeroRPC;

use ZMQ;

class Context extends \ZMQContext
{
    private $hooks = array(
        'resolve_endpoint' => array(),
        'before_send_request' => array(),
        'after_response' => array(),
    );

    private static $instance = null;

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new Context();
        }
        return self::$instance;
    }

    public function registerHook($name, $func)
    {
        if (isset($this->hooks[$name]) && is_callable($func)) {
            array_push($this->hooks[$name], $func);
        }
    }

    public function hookResolveEndpoint($endpoint, $version)
    {
        $endpoint_ = false;
        foreach ($this->hooks['resolve_endpoint'] as $func) {
            $endpoint_ = $func($endpoint, $version);
        }
        return $endpoint_ ?: $endpoint;
    }

    public function hookBeforeSendRequest($event, $client)
    {
        foreach ($this->hooks['before_send_request'] as $func) {
            $func($event, $client);
        }
    }

    public function hookAfterResponse($event, $client)
    {
        foreach ($this->hooks['after_response'] as $func) {
            $func($event, $client);
        }
    }
}

class Client
{
    private $context;
    private $socket;
    private $timeout = 600;

    public function __construct($endpoint = null, $version = null, $context = null)
    {
        $this->_endpoint = $endpoint;
        $this->_version = $version;
        $this->context = $context ?: Context::get_instance();
        $this->socket = new \ZMQSocket($this->context, ZMQ::SOCKET_XREQ);
        $this->socket->setSockOpt(ZMQ::SOCKOPT_LINGER, 10);
        $this->connect($endpoint, $version);
        Channel::registerSocket($this->socket);
    }

    public function __call($name, $args)
    {
        $response  = $this->sync($name, $args);
        return $response;   
    }

    public function connect()
    {
        $endpoint = $this->context->hookResolveEndpoint($this->_endpoint, $this->_version);
        $this->socket->connect($endpoint);
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function sync($name, array $args, $timeout = 0)
    {
        if (!$timeout) {
            $timeout = $this->timeout;
        }

        $event = new Request($name, $args, uniqid(posix_getpid()));
        $this->context->hookBeforeSendRequest($event, $this);
        $this->socket->sendMulti($event->serialize());

        $read = $write = array();
        $poll = new \ZMQPoll();
        $poll->add($this->socket, ZMQ::POLL_IN);
        $events = $poll->poll($read, $write, $timeout);

        if ($events) {
            $recv = $this->socket->recvMulti();
            $event = Response::deserialize($recv);
            $this->context->hookAfterResponse($event, $this);
            return $event->getContent(); 
        } else {
            throw new TimeoutException('Timout after ' . $this->timeout .' ms');
        }
    }

    public function async($name, array $args, &$response) 
    {
        $event = new Request($name, $args);
        $this->context->hookBeforeSendRequest($event, $this);
        Channel::startRequest($this->socket, $event, $response);
        $this->context->hookAfterResponse($event, $this);
    }

}


