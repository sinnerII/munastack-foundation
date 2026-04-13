<?php

namespace Munastack\Foundation;

use Munastack\Config\Repository;
use Munastack\Support\Container;

class Application
{
    private static Application $instance;
    private array $providers = [];

    public protected(set) Repository $config;
    public protected(set) Container $container;

    public protected(set) float $startTime;
    // phpcs:disable
    public string $timeDuration {
        get => number_format(microtime(true) - $this->startTime,3, '.', '') . 'sec';
    }
    // phpcs:enable

    public function __construct(public protected(set) string $basePath)
    {
        self::$instance = $this;
        $this->startTime = microtime(true);
        $this->container = new Container();
    }

    public static function getInstance(): Application
    {
        return self::$instance;
    }

    public function init(): void
    {
        $this->loadConfig();
        $this->loadProviders();
        $this->container->get('router')->dispatch();

        dump($this->timeDuration);
    }

    private function loadConfig(): void
    {
        $result = [];
        $configPath = $this->basePath . '/config';

        if (is_dir($configPath)) {
            $configFiles = glob($configPath . '/*.php');
            foreach ($configFiles as $file) {
                $result[pathinfo($file)['filename']] = require $file;
            }
        }

        $this->config = new Repository($result);
    }

    private function loadProviders(): void
    {
        foreach ($this->config->get('app.providers') as $provider) {
            $providerInstance = new $provider(self::$instance);
            $this->providers[] = $providerInstance;
            $providerInstance->register();
        }

        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }
}
