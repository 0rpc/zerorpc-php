<?php
namespace ZeroRPC;

class RPCException extends \Exception {}

class EventException extends RPCException {}

class ClientException extends RPCException {}

class TimeoutException extends RPCException {
    public function __toString() {
        return <<<EOT
RPC Timeout Exception:
{$this->message}
Called in {$this->getFile()}:{$this->getLine()}
PHP stack trace:
{$this->getTraceAsString()}

EOT;
    }
}

class RemoteException extends RPCException {
    private $name;
    private $representation;

    public function __construct(array $exception, $code = 0, Exception $previous = null) {
        $this->name = $exception[0];
        $this->representation = $exception[1];
        $message = $exception[2];
    
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return <<<EOT
RPC Remote Exception [{$this->name} - {$this->representation}]:
{$this->message}Called in {$this->getFile()}:{$this->getLine()}
PHP stack trace:
{$this->getTraceAsString()}


EOT;
    }
}
