<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Chiefgroup\Http\Client;

use Chiefgroup\Http\Support\Collection;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use Chiefgroup\Http\Exceptions\HttpException;
use Chiefgroup\Http\Support\Log;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Http extends AccessToken
{
    /**
     * Used to identify handler defined by client code
     * Maybe useful in the future.
     */
    const USER_DEFINED_HANDLER = 'userDefined';

    /**
     * Http client.
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * The middlewares.
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Guzzle client default settings.
     *
     * @var array
     */
    protected static $defaults = [];

    /**
     * Base URL Api
     */
    protected $base_uri;

    /**
     * Config
     */
    protected $config;

    /**
     * @var int
     */
    protected $maxRetries = 2;

    /**
     * Http constructor.
     * @param  Collection  $confing
     */
    public function __construct(Collection $confing)
    {
        $this->config = $confing;

        $this->base_uri = $confing->get('servers.default.base_uri');

        $this->maxRetries = $confing->get('servers.default.max_retries');

        parent::__construct($this->config);
    }

    /**
     * GET request.
     *
     * @param  string  $url
     *
     * @param  array  $options
     * @param  array  $headers
     * @return ResponseInterface
     *
     */
    public function get($url, array $options = [], $headers = [])
    {
        return $this->request($url, 'GET', ['query' => $options, 'headers' => $headers]);
    }

    /**
     * POST request.
     *
     * @param  string  $url
     * @param  array|string  $options
     *
     * @param  array  $headers
     * @return ResponseInterface
     *
     */
    public function post($url, $options = [], $headers = [])
    {
        $key = is_array($options) ? 'json' : 'body';

        return $this->request($url, 'POST', [$key => $options, 'headers' => $headers]);
    }

    /**
     * @param $url
     * @param  array  $options
     * @param  array  $headers
     * @return ResponseInterface
     */
    public function put($url, $options = [], $headers = [])
    {
        $key = is_array($options) ? 'json' : 'body';

        return $this->request($url, 'PUT', [$key => $options, 'headers' => $headers]);
    }

    /**
     * @param $url
     * @param  array  $options
     * @param  array  $headers
     * @return ResponseInterface
     */
    public function patch($url, $options = [], $headers = [])
    {
        $key = is_array($options) ? 'form_params' : 'body';

        return $this->request($url, 'PATCH', [$key => $options, 'headers' => $headers]);
    }

    /**
     * @param $url
     * @param  array  $options
     * @param  array  $headers
     * @return ResponseInterface
     */
    public function delete($url, $options = [], $headers = [])
    {
        $key = is_array($options) ? 'form_params' : 'body';

        return $this->request($url, 'DELETE', [$key => $options, 'headers' => $headers]);
    }

    /**
     * JSON request.
     *
     * @param  string  $url
     * @param  string|array  $options
     * @param  array  $headers
     * @param  array  $queries
     * @param  int  $encodeOption
     *
     * @return ResponseInterface
     *
     */
    public function json($url, $options = [], $headers = [], $queries = [], $encodeOption = JSON_UNESCAPED_UNICODE)
    {
        is_array($options) && $options = json_encode($options, $encodeOption);

        $data = ['body' => $options, 'headers' => array_merge($headers, ['content-type' => 'application/json'])];

        if (!empty($queries)) {
            $data['query'] = $queries;
        }

        return $this->request($url, 'POST', $data);
    }

    /**
     * Set GuzzleHttp\Client.
     *
     * @return Http
     */
    public function setClient(HttpClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Return GuzzleHttp\Client instance.
     *
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        if (!($this->client instanceof HttpClient)) {
            $this->client = new HttpClient();
        }

        return $this->client;
    }

    /**
     * Add a middleware.
     *
     * @return $this
     */
    public function addMiddleware(callable $middleware)
    {
        array_push($this->middlewares, $middleware);

        return $this;
    }

    /**
     * Return all middlewares.
     *
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Register Guzzle middlewares.
     */
    protected function registerHttpMiddlewares()
    {
        // exception handler
        $this->addMiddleware($this->exceptionHandlerMiddleware());
        // log
        $this->addMiddleware($this->logMiddleware());
        // retry
        $this->addMiddleware($this->retryMiddleware());
        // Authorization
        $this->addMiddleware($this->authorizationMiddleware());
    }

    /**
     * Return retry middleware.
     *
     * @return \Closure
     */
    protected function retryMiddleware()
    {
        return Middleware::retry(function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null
        ) {
            // defult Limit the number of retries to 2
            if ($retries <= $this->maxRetries && $response && $body = $response->getBody()) {
                // Retry on server errors
                if ((int)$response->getStatusCode() === 401) {

                    $headers = $request->getHeaders();

                    if (!empty($headers['Authorization']) && !empty($headers['refresh_token'])) {
                        $request = $request->withHeader('Authorization', $this->refreshToken($headers['refresh_token']));
                    } else {
                        $request = $request->withHeader('Authorization', $this->getToken(false));
                    }

                    $request->withHeader('Content-Type', 'application/json');

                    Log::debug("Retry with Request Token: {".json_encode($headers)."}");

                    return true;
                }
            }

            return false;
        });
    }

    /**
     * authorization middleware.
     * @return \Closure
     */
    protected function authorizationMiddleware()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $request = $request->withHeader('Authorization', $this->getToken())->withHeader('Accept', 'application/json');
                return $handler($request, $options);
            };
        };
    }

    /**
     * Exception handler middleware.
     * @return \Closure
     */
    protected function exceptionHandlerMiddleware()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                try {
                    $response = $handler($request, $options);
                    return $response;
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    // 记录请求异常到日志
                    Log::error('Guzzle HTTP Request Exception:', [
                        'exception' => $e->getMessage(),
                        'request' => [
                            'method' => $request->getMethod(),
                            'uri' => (string) $request->getUri(),
                            'headers' => $request->getHeaders(),
                            'body' => $this->getRequestBody($request),
                        ],
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    
                    // 抛出封装的异常
                    throw new HttpException(null, $e->getCode(), $e->getMessage(), $e);
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    // 记录客户端异常
                    $response = $e->getResponse();
                    Log::error('Guzzle HTTP Client Exception:', [
                        'exception' => $e->getMessage(),
                        'request' => [
                            'method' => $request->getMethod(),
                            'uri' => (string) $request->getUri(),
                            'headers' => $request->getHeaders(),
                            'body' => $this->getRequestBody($request),
                        ],
                        'response' => [
                            'status_code' => $response->getStatusCode(),
                            'headers' => $response->getHeaders(),
                            'body' => (string) $response->getBody(),
                        ],
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    
                    throw new HttpException($response, $e->getCode(), $e->getMessage(), $e);
                } catch (\GuzzleHttp\Exception\ServerException $e) {
                    // 记录服务器异常
                    $response = $e->getResponse();
                    Log::error('Guzzle HTTP Server Exception:', [
                        'exception' => $e->getMessage(),
                        'request' => [
                            'method' => $request->getMethod(),
                            'uri' => (string) $request->getUri(),
                            'headers' => $request->getHeaders(),
                            'body' => $this->getRequestBody($request),
                        ],
                        'response' => [
                            'status_code' => $response->getStatusCode(),
                            'headers' => $response->getHeaders(),
                            'body' => (string) $response->getBody(),
                        ],
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    
                    throw new HttpException($response, $e->getCode(), $e->getMessage(), $e);
                } catch (\GuzzleHttp\Exception\ConnectException $e) {
                    // 记录连接异常
                    Log::error('Guzzle HTTP Connection Exception:', [
                        'exception' => $e->getMessage(),
                        'request' => [
                            'method' => $request->getMethod(),
                            'uri' => (string) $request->getUri(),
                            'headers' => $request->getHeaders(),
                            'body' => $this->getRequestBody($request),
                        ],
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    
                    throw new HttpException(null, $e->getCode(), $e->getMessage(), $e);
                } catch (\Exception $e) {
                    // 记录其他异常
                    Log::error('Guzzle HTTP General Exception:', [
                        'exception' => $e->getMessage(),
                        'request' => [
                            'method' => $request->getMethod(),
                            'uri' => (string) $request->getUri(),
                            'headers' => $request->getHeaders(),
                            'body' => $this->getRequestBody($request),
                        ],
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    
                    throw new HttpException(null, $e->getCode(), $e->getMessage(), $e);
                }
            };
        };
    }

    /**
     * Get request body content.
     *
     * @param RequestInterface $request
     * @return string
     */
    protected function getRequestBody(RequestInterface $request)
    {
        $body = $request->getBody();
        if ($body instanceof StreamInterface) {
            $content = (string) $body;
            // Reset body position if possible
            if ($body->isSeekable()) {
                $body->rewind();
            }
            return $content;
        }
        return '';
    }

    /**
     * Make a request.
     *
     * @param  string  $url
     * @param  string  $method
     * @param  array  $options
     *
     * @return ResponseInterface
     */
    public function request($url, $method = 'GET', $options = [])
    {
        if (0 === count($this->getMiddlewares())) {
            $this->registerHttpMiddlewares();
        }

        $method = strtoupper($method);

        $options = array_merge(self::$defaults, $options);

        $options['handler'] = $this->getHandler();

        if (!preg_match('/(http:\/\/)|(https:\/\/)/i', $url)) {
            $url = $this->base_uri. '/'. $url;
        }

        $response = $this->getClient()->request($method, $url, $options);

        Log::debug('API response:', [
            'Status'  => $response->getStatusCode(),
            'Reason'  => $response->getReasonPhrase(),
            'Headers' => $response->getHeaders(),
            'Body'    => strval($response->getBody()),
        ]);

        return $this->parseJSON($response);
    }

    /**
     * @param  \Psr\Http\Message\ResponseInterface|string  $body
     *
     * @return mixed
     *
     * @throws HttpException
     */
    public function parseJSON($body)
    {
        if ($body instanceof ResponseInterface) {
            $body = mb_convert_encoding($body->getBody(), 'UTF-8');
        }

        if (empty($body)) {
            return false;
        }

        $contents = json_decode($body, true, 512, JSON_BIGINT_AS_STRING);

        Log::debug('API response decoded:', compact('contents'));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new HttpException('Failed to parse JSON: '.json_last_error_msg());
        }

        return $contents;
    }

    /**
     * Build a handler.
     *
     * @return HandlerStack
     */
    protected function getHandler()
    {
        $stack = HandlerStack::create();

        foreach ($this->middlewares as $middleware) {
            $stack->push($middleware);
        }

        if (isset(static::$defaults['handler']) && is_callable(static::$defaults['handler'])) {
            $stack->push(static::$defaults['handler'], self::USER_DEFINED_HANDLER);
        }

        return $stack;
    }
}
