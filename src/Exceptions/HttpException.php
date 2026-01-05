<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Chiefgroup\Http\Exceptions;

class HttpException extends Exception
{
    public function __construct($response = null, $code = 0, $message = 'HTTP request failed', \Exception $previous = null)
    {
        parent::__construct($response, $code, $message, $previous);
    }
}
