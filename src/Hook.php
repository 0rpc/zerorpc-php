<?php
namespace ZeroRPC\Hook;

use ZeroRPC\ClientException;

class ConfigMiddleware
{

    function __construct($config)
    {
        $this->config = $config;
    }

    public function getConfigName($name)
    {
        $configName = "ZERORPC_" . strtoupper($name);
        if (!isset($this->config[$configName])) {
            throw new ClientException("Missing config $configName.");
        }
        return $configName;
    }

    public function getVersion($name, $version)
    {
        $configName = $this->getConfigName($name);
        if (!$version) {
            if (isset($this->config[$configName]['default'])) {
                $version = $this->config[$configName]['default'];
            } else {
                throw new ClientException("Missing version in the request.");
            }
            if (!isset($this->config[$configName][$version])) {
                $exception = "Missing config {$configName}['{$version}'].";
                throw new ClientException($exception);
            }
        }
        return $version;
    }

    public function getAccessKey($name)
    {
        $configName = $this->getConfigName($name);
        if (isset($this->config[$configName]['access_key'])) {
            return $this->config[$configName]['access_key'];
        } else {
            throw new ClientException("Missing access_key in the {$configName}.");
        }
    }

    public function resolveEndpoint()
    {
        return function ($name, $version) {
            $configName = $this->getConfigName($name);
            $version = $this->getVersion($name, $version);
            $config = $this->config[$configName][$version];
            if (is_array($config)) {
                $endpoint = $config[array_rand($config)];
            } else {
                $endpoint = $config;
            }
            return $endpoint;
        };
    }

    public function beforeSendRequest()
    {
        return function ($event, $client) {
            $event->header['access_key'] = $this->getAccessKey($client->_endpoint);
            $event->header['service_version'] = $this->getVersion($client->_endpoint,
                                                                  $client->_version);
            $event->header['service_name'] = $client->_endpoint;
        };
    }
}
