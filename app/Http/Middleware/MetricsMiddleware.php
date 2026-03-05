<?php

namespace App\Http\Middleware;

use Closure;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Counter;
use Prometheus\Histogram;
use Illuminate\Http\Request;

class MetricsMiddleware
{
    protected $registry;
    protected $counterHits;
    protected $counterErrors;
    protected $histogram;

    public function __construct()
    {
        // Gunakan InMemory storage (ubah ke APC/Redis/File untuk persistence)
        $this->registry = new CollectorRegistry(new InMemory());

        // Counter hits per endpoint
        $this->counterHits = $this->registry->registerCounter(
            'app',
            'page_hits',
            'Jumlah hits per endpoint',
            ['endpoint']
        );

        // Counter error per endpoint
        $this->counterErrors = $this->registry->registerCounter(
            'app',
            'endpoint_errors',
            'Jumlah error per endpoint',
            ['endpoint', 'status']
        );

        // Histogram response time
        $this->histogram = $this->registry->registerHistogram(
            'app',
            'response_time_seconds',
            'Response time per endpoint',
            ['endpoint'],
            [0.1, 0.3, 0.5, 1, 2, 5, 10]
        );
    }

    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;

        $endpoint = $request->path();

        // Hit counter
        $this->counterHits->inc([$endpoint]);

        // Error counter jika status >= 400
        if ($response->getStatusCode() >= 400) {
            $this->counterErrors->inc([$endpoint, (string)$response->getStatusCode()]);
        }

        // Response time histogram
        $this->histogram->observe($duration, [$endpoint]);

        return $response;
    }

    // Method untuk expose registry
    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }
}
