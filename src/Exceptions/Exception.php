<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Chiefgroup\Http\Exceptions;

class Exception extends \RuntimeException
{

    protected $response;

    public function __construct($response, $code, $message = 'No message.', \Exception $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function __get($key)
    {
        return $this->response->$key;
    }
}
