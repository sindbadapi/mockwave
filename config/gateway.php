<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxy Timeout
    |--------------------------------------------------------------------------
    | Maximum seconds to wait for a response from the real microservice
    | when operating in proxy mode.
    */
    'timeout_seconds' => (int) env('GATEWAY_TIMEOUT_SECONDS', 30),

    /*
    |--------------------------------------------------------------------------
    | Request / Response Body Logging
    |--------------------------------------------------------------------------
    | When enabled, the raw body of each request and response is stored in
    | request_logs. Disable for high-traffic services to reduce DB pressure.
    */
    'log_request_body' => (bool) env('GATEWAY_LOG_REQUEST_BODY', true),
    'log_response_body' => (bool) env('GATEWAY_LOG_RESPONSE_BODY', true),

    /*
    |--------------------------------------------------------------------------
    | Max Log Body Size
    |--------------------------------------------------------------------------
    | Maximum number of bytes to persist per body field.
    | 0 means unlimited (not recommended in production).
    */
    'max_log_body_size' => (int) env('GATEWAY_MAX_LOG_BODY_SIZE', 65536),

];
