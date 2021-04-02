<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Chiefgroup\Http\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class LaravelGuzzleHttp
 * @package Chiefgroup\Http\Facades
 *
 * @method static get(string $uri, array $options = [], array $headers = [])
 * @method static put(string $uri, array $options = [], array $headers = [])
 * @method static post(string$uri, array $options = [], array $headers = [])
 * @method static patch(string $uri, array $options = [], array $headers = [])
 * @method static delete(string $uri, array $options = [], array $headers = [])
 *
 */
class LaravelGuzzleHttp extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() : string { return 'laravel-guzzle-http'; }
}
