<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class MetricsMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $registry = new CollectorRegistry(new InMemory());
        $counter = $registry->getOrRegisterCounter('app', 'page_hits', 'Hits per page', ['page']);
        $counter->inc([$request->path()]);

        return $response;
    }
}
