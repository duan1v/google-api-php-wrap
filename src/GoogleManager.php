<?php

namespace Dywily\Gaw;

use Google\Client;
use Google\Service\Gmail;

class GoogleManager
{
    public static array $config = [];

    protected array $accounts = [];

    public string $serviceName = '';

    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    public function setConfig($config): GoogleManager
    {
        if (is_array($config) === false) {
            $config = require $config;
        }

        $config_key = 'gaw';
        $path = __DIR__ . '/config/' . $config_key . '.php';

        $vendor_config = require $path;
        $config = $this->array_merge_recursive_distinct($vendor_config, $config);

        if (is_array($config)) {
            if (isset($config['default'])) {
                if (isset($config['accounts']) && $config['default']) {

                    $default_config = $vendor_config['accounts']['default'];
                    if (isset($config['accounts'][$config['default']])) {
                        $default_config = array_merge($default_config, $config['accounts'][$config['default']]);
                    }

                    if (is_array($config['accounts'])) {
                        foreach ($config['accounts'] as $account_key => $account) {
                            $config['accounts'][$account_key] = array_merge($default_config, $account);
                        }
                    }
                }
            }
        }

        self::$config = $config;

        return $this;
    }

    private function array_merge_recursive_distinct()
    {
        $arrays = func_get_args();
        $base = array_shift($arrays);

        $isAssoc = function (array $arr) {
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        };

        if (!is_array($base)) $base = empty($base) ? array() : array($base);

        foreach ($arrays as $append) {
            if (!is_array($append)) $append = array($append);
            foreach ($append as $key => $value) {
                if (!array_key_exists($key, $base) and !is_numeric($key)) {
                    $base[$key] = $value;
                    continue;
                }
                if ((is_array($value) && $isAssoc($value))
                    || (is_array($base[$key]) && $isAssoc($base[$key]))) {
                    $base[$key] = $this->array_merge_recursive_distinct($base[$key], $value);
                } else if (is_numeric($key)) {
                    if (!in_array($value, $base)) $base[] = $value;
                } else {
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }

    public function account(string $serviceName, string $name = null): Client
    {
        $name = $name ?: $this->getDefaultAccount();
        $this->serviceName = $serviceName;
        if (!isset($this->accounts[$name])) {
            $this->accounts[$name] = $this->resolve($name);
        }

        return $this->accounts[$name];
    }

    /**
     * @param $client
     * @return Gmail
     * @throws \ReflectionException
     */
    public function initService($client): Gmail
    {
        $config = static::$config['accounts'][static::$config['default']];
        $configKey = $this->serviceName . '_service';
        $className = 'Dywily\Gaw\Services\\' . ucfirst($this->serviceName) . 'Service';
        if (!empty(static::$config[$configKey])) {
            static::$config[$configKey];
        }
        if (!empty($config[$configKey])) {
            $config[$configKey];
        }
        $class = new \ReflectionClass($className);
        return $class->getMethod('service')->invoke(null, $client, $config);
    }

    /**
     * @param string $name
     * @return Client
     */
    protected function resolve(string $name): Client
    {
        $config = $this->getClientConfig($name);

        return GoogleClient::getInstance($config);
    }

    protected function getClientConfig($name): array
    {
        if ($name === null || $name === 'null') {
            return ['driver' => 'null'];
        }

        return is_array(self::$config["accounts"][$name]) ? self::$config["accounts"][$name] : [];
    }

    /**
     * @return string
     */
    public function getDefaultAccount(): string
    {
        return self::$config['default'];
    }
}
