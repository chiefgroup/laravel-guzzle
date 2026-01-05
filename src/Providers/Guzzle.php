<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Chiefgroup\Http\Providers;

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

        // 设置自定义日志 channel
        $logChannel = $this->config->get('log.channel');
        if ($logChannel) {
            Log::setChannel($logChannel);
        }
    }
}
