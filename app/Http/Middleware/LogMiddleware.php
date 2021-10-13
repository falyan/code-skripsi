<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogMiddleware
{
    protected $except_urls = [
        'v1/detail',
    ];

    public function handle($request, Closure $next)
    {
        $uid = Str::random(12);

        $response = $next($request);

        $log_response = $response->original;
        if (!empty($log_response['token'])) {
            $log_response['token'] = "hiddentoken";
        }
        
        Log::info($uid, [
            'path_url' => $request->path(),
            'query' =>  $request->query(),
            'body' => $request->except(['password', 'c_password', 'bearer', 'bearer_token', 'related_id', 'related_customer_id']),
            'response' => $log_response
        ]);

        return $response;
    }
}
