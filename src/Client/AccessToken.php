<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Chiefgroup\Http\Client;

use Chiefgroup\Http\Exceptions\HttpException;
use Chiefgroup\Http\Support\Collection;
use Chiefgroup\Http\Support\Log;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

class AccessToken
{
    protected $http;

    protected $config;
    protected $base_uri;
    protected $scope;
    protected $grant_type;
    protected $client_id;
    protected $client_secret;

    /**
     * AccessToken constructor.
     * @param  Collection  $config
     */
    public function __construct(Collection $config)
    {
        $this->config = $config;

        $this->base_uri = $config->get('servers.default.base_uri');

        $this->scope = $config->get('servers.default.scope');

        $this->grant_type = $config->get('servers.default.grant_type');

        $this->client_id = $config->get('servers.default.client_id');

        $this->client_secret = $config->get('servers.default.client_secret');
    }

    /**
     * @param  string  $refresh_token
     * @return mixed
     */
    public function refreshToken(string $refresh_token)
    {
        $params = [
            'grant_type'    => $this->grant_type,
            'refresh_token' => $refresh_token,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope'         => $this->scope,
        ];

        $http = $this->getHttp();

        $result = $http->parseJSON($http->post('oauth/token', $params));

        if (empty($result['data'])) {
            throw new HttpException('Refresh AccessToken fail. response: '.json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    public function getHttp()
    {
        $this->http = $this->http ?: $this->http = new Http($this->config);

        $this->http->addMiddleware($this->logMiddleware());

        return $this->http;
    }

    public function setHttp($http)
    {
        $this->http = $http;

        return $this;
    }

    /**
     * Log the request.
     *
     * @return \Closure
     */
    protected function logMiddleware()
    {
        return Middleware::tap(function (RequestInterface $request, $options) {
            Log::debug("Refresh Request: {$request->getMethod()} {$request->getUri()} ".json_encode($options));
            // Log::debug('Request headers:'.json_encode($request->getHeaders()));
        });
    }
}
