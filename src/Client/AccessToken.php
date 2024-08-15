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
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\RequestInterface;

class AccessToken
{
    protected $http;

    protected $config;
    protected $base_uri;
    protected $scope;
    protected $client_id;
    protected $client_secret;
    protected $username;

    /**
     * AccessToken constructor.
     * @param  Collection  $config
     */
    public function __construct(Collection $config)
    {
        $this->config = $config;

        $this->base_uri = $config->get('servers.default.base_uri');

        $this->scope = $config->get('servers.default.scope');

        $this->client_id = $config->get('servers.default.client_id');

        $this->client_secret = $config->get('servers.default.client_secret');

        $this->username = $config->get('servers.default.username');
    }

    /**
     * @param  string  $refresh_token
     * @return string
     */
    public function refreshToken(string $refresh_token)
    {
        $params = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope'         => $this->scope,
        ];

        $http = $this->getHttp();

        $result = $http->post('oauth/token', $params);

        if (empty($result)) {
            throw new HttpException('Refresh AccessToken fail. response null');
        }

        if (!empty($result['access_token'])) {
            Cache::set($this->username, $result['token_type'].' '.$result['access_token'], $result['expires_in']);
        }

        return $result['access_token'] ?? null;
    }

    /**
     * @return Http
     */
    public function getHttp()
    {
        $this->http = $this->http ?: $this->http = new Http($this->config);

        $this->http->addMiddleware($this->logMiddleware());

        return $this->http;
    }

    /**
     * @param $http
     * @return $this
     */
    public function setHttp($http)
    {
        $this->http = $http;

        return $this;
    }

    /**
     * @param bool $isCache
     * @return string
     */
    public function getToken(bool $isCache = true)
    {
        if ($isCache && Cache::has($this->username)) {
            return Cache::get($this->username);
        }

        $http = $this->getHttp();

        $result = $http->post('oauth/token', [
            'grant_type'    => 'password',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope'         => $this->scope,
            'username'      => $this->username,
            'password'      => $this->username,
        ]);

        if (empty($result)) {
            throw new HttpException('Get AccessToken fail. response null');
        }

        if (!empty($result['access_token'])) {
            Cache::set($this->username, $result['token_type'].' '.$result['access_token'], $result['expires_in']);
        }

        return $result['access_token'] ?? null;
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
            Log::debug('Request headers:'.json_encode($request->getHeaders()));
        });
    }
}
