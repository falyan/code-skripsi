<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogMiddleware
{
    protected $except_urls = [
        'v1/detail',
        'logs',
        'logs/*',
        'logs/*/*',
    ];

    public function handle($request, Closure $next)
    {
        $uid = Str::random(12);

        $response = $next($request);

        $log_response = $response->original ?? null;
        if (data_get($log_response, 'token')) {
            $log_response['token'] = "hiddentoken";
        }

        if (data_get($log_response, 'data') && data_get($log_response['data'], 'token')) {
            $log_response['data']['token'] = "hiddentoken";
        }

        // except_urls
        if (in_array($request->path(), $this->except_urls)) {
            return $response;
        }

        Log::info($uid, [
            'path_url' => $request->path(),
            'query' =>  $request->query(),
            'body' => $request->except(['password', 'old_password', 'password_confirmation', 'bearer', 'bearer_token', 'related_id', 'related_customer_id']),
            'response' => $log_response
        ]);

        return $response;
    }
}
