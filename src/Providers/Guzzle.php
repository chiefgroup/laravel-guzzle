<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Chiefgroup\Http\Providers;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Chiefgroup\Http\Support\Log;
use Chiefgroup\Http\Support\Collection;
use Chiefgroup\Http\Client\Http;
use Illuminate\Support\Facades\Config;

class Guzzle extends Http
{
    protected $config;

    /**
     * Guzzle constructor.
     */
    public function __construct()
    {
        $config = Config::get('api_config');

        $this->config = new Collection($config);

        $this->initializeLogger();

        parent::__construct($this->config);
    }

    private function initializeLogger()
    {
        if (Log::hasLogger()) {
            return;
        }

        $logger = new Logger('laravel-guzzle-http');

        if (!$this->config->get('debug') || defined('PHPUNIT_RUNNING')) {
            $logger->pushHandler(new NullHandler());
        } elseif ($logFile = $this->config->get('log.file')) {
            try {
                $logger->pushHandler(new StreamHandler($logFile, $this->config->get('log.level', Logger::DEBUG), true, null));
            } catch (\Exception $e) {
            }
        }
        Log::setLogger($logger);
    }
}
