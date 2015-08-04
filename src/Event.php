<?php
namespace ZeroRPC;

class Event 
{
    const VERSION = 3;

    public $envelope;
    public $header;
    

    public function getMessageID()
    {
        return $this->header['message_id'];
    }
}

class Request extends Event
{
    public $name;
    public $args;
    public $header;

    public function __construct(
        $name, 
        $args = null, 
        $messageID = null, 
        $responseTo = null, 
        $envelope = null
    ) {
        $this->name = $name;
        $this->args = $args;
        $this->header = $this->genHeader($messageID, $responseTo);
        $this->envelope = $envelope;
    }

    public function genHeader($messageID, $responseTo) 
    {
        $header['v'] = self::VERSION;
        $header['message_id'] = $messageID ? $messageID : uniqid();
        if ($responseTo) {
            $header['response_to'] = $responseTo;
        }
        return $header;
    }

    public function serialize() 
    {
        $payload = array($this->header, $this->name, $this->args);
        $message = ($this->envelope) ? $this->envelope : array(null);
        array_push($message, msgpack_pack($payload));
        return $message;
    }

}

class Response extends Event
{
    public $status;
    public $content;

    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERR';

    public function __construct($envelope, array $header, $status, $content) 
    {
        $this->envelope = $envelope;
        $this->header = $header;
        $this->status = $status;
        $this->content = $content;
    }

    public static function deserialize($recv) 
    {
        $envelope = array_slice($recv, 0, count($recv)-2);
        $payload = $recv[count($recv)-1];

        $event = msgpack_unpack($payload);
        if (!is_array($event) || count($event) !== 3) {
            throw new EventException('Expected array of size 3');
        } else if (!is_array($event[0]) || !array_key_exists('message_id', $event[0])) {
            throw new EventException('Bad header');
        } else if (!is_string($event[1])) {
            throw new EventException('Bad name');
        }
        return new Response($envelope, $event[0], $event[1], $event[2]);
    }

    public function getContent()
    {
        if ($this->status == self::STATUS_OK) {
            return $this->content[0];    
        }

        if ($this->status == self::STATUS_ERROR) {
            throw new RemoteException($this->content);
        }
        
    }
}
