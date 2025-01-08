<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class LogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 요청 URI를 로그에 기록
        Log::info('Request URL: ' . $request->fullUrl());

        return $next($request); // 다음 단계로 요청 전달
    }
}

