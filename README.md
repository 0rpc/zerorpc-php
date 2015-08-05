zerorpc PHP Client
===========

## Quick start

+ check dependences

```
$ composer install
```

+ recommand installation for Mac

```
$ brew install zeromq --universal
$ brew install php56
$ brew install php56-msgpack
$ brew install php56-zmq
```

+ Installing zerorpc on Ubuntu

```
$ sudo pecl install channel://pecl.php.net/msgpack-0.5.5
$ sudo apt-get install pkg-config
$ git clone git://github.com/mkoppanen/php-zmq.git
$ cd php-zmq && sudo phpize && ./configure
$ sudo make && make install
```

_Note: Don't forget to add the extensions to your php.ini_

```
extension=msgpack.so
extension=zmq.so
```

## Timeout Setting

+ `$timeout` is in milliseconds
+ `$client->setTimeout($timeout)` is only work on sync calls
+ `Channel::dispatch($timeout)` is only work on async calls

## Example

**Server:**

Read [official python server guide][1] and start two simple `time` server.

```
$ zerorpc --server --bind tcp://*:1234 time
$ zerorpc --server --bind tcp://*:2345 time
```
**Client:**

```
$ php example/time.php
```

This will return:

```
Example 1: start sync call:
cost 5.0087389945984 s
Example 2: start async call:
cost 3.0069580078125 s
```

[1]: https://github.com/0rpc/zerorpc-python
