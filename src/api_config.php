<?php

/*
 * This file is part of the chiefgroup/laravel-guzzle.
 *
 * (c) peng <2512422541@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

return [
    /**
     * You can declare as much servers you want. Here is an example
     * on `default` server, you may override or delete it.
     */
    'servers' => [

        /**
         * This is the default server for example purpose
         *
         * All request otpions sent on every http request.
         */
        'default' => [
            // Base URI
            'base_uri'      => '',
            // Scope
            'scope'         => '*',
            // Refresh Token
            'grant_type'    => 'refresh_token',
            // Client Id
            'client_id'     => 1,
            // Client Secret
            'client_secret' => 'xxxxxx',
            // Retries count
            'max_retries'   => 2,
        ]
    ],

    /**
     * You may want to log everything.
     */
    'debug'   => false,

    /**
     * Log filePath.
     */
    'log'     => [
        'file' => 'storage/logs/guzzle_http.log'
    ]
];
