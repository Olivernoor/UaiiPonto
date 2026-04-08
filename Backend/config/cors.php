<?php

return [
    /**
     * Paths that should be proxy'd by CORS middleware. If a path matches
     * the prefix, the CORS middleware handling will be triggered.
     */
    'paths' => ['*'],

    /**
     * Paths that should be excluded from CORS middleware
     */
    'except' => [],

    /**
     * The allowed HTTP methods.
     */
    'allowed_methods' => ['*'],

    /**
     * The allowed HTTP headers.
     */
    'allowed_origins' => ['*'],

    /**
     * The headers that are allowed in CORS requests.
     */
    'allowed_headers' => ['*'],

    /**
     * The exposed CORS headers.
     */
    'exposed_headers' => [],

    /**
     * The max age of the CORS preflight request in seconds.
     */
    'max_age' => 0,

    /**
     * Indicates whether the CORS middleware should set credentials
     * on the preflight request.
     */
    'supports_credentials' => true,
];
