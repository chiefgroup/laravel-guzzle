<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Chiefgroup\Http\Support;

use Illuminate\Support\Facades\Log as LaravelLog;
use Psr\Log\LoggerInterface;

class Log
{
    protected static $logger;
    protected static $channel = null;

    public static function getLogger()
    {
        if (!self::$logger) {
            if (self::$channel) {
                self::$logger = LaravelLog::channel(self::$channel);
            } else {
                self::$logger = LaravelLog::channel('default');
            }
        }
        return self::$logger;
    }

    /**
     * Set logger.
     */
    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    /**
     * Set log channel.
     */
    public static function setChannel($channel)
    {
        self::$channel = $channel;
        // Reset logger to use new channel
        self::$logger = null;
    }

    /**
     * Get current log channel.
     */
    public static function getChannel()
    {
        return self::$channel;
    }

    /**
     * Tests if logger exists.
     *
     * @return bool
     */
    public static function hasLogger()
    {
        return self::$logger ? true : false;
    }

    /**
     * Forward call.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return forward_static_call_array([self::getLogger(), $method], $args);
    }

    /**
     * Forward call.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([self::getLogger(), $method], $args);
    }
}
