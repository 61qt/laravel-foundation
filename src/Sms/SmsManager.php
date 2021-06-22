<?php

namespace QT\Foundation\Sms;

use InvalidArgumentException;
use QT\Foundation\Contracts\SmsClient;

class SmsManager
{
    protected $config;

    protected $clients;

    /**
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get sms service client.
     *
     * @param  string|null  $name
     * @return SmsClient
     */
    public function getClient($name = null)
    {
        $name = $name ?: $this->config['default'];

        if (isset($this->clients[$name])) {
            return $this->clients[$name];
        }

        return $this->clients[$name] = $this->resolve($name);
    }

    /**
     * Resolve sms service.
     *
     * @param  string|null  $name
     * @return SmsClient
     */
    protected function resolve($name)
    {
        $config = $this->config[$name] ?? [];

        switch ($name) {
            case 'ums86':
                return new Clients\Ums86Sms($config);
            case 'alidayu':
                return new Clients\AlidayuSms($config);
            default:
                throw new InvalidArgumentException("Sms service [{$name}] not configured.");
        }
    }

    /**
     * @param  string  $method
     * @param  array  $parameters
     * @return SmsClient
     */
    public function __call($method, $parameters)
    {
        return $this->getClient()->{$method}(...$parameters);
    }
}
