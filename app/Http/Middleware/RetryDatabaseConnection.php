<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class RetryDatabaseConnection
{
    public function handle($request, Closure $next)
    {
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                DB::connection()->getPdo();
                break; // kalau berhasil langsung lanjut
            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $maxAttempts) {
                    throw $e; // kalau tetap gagal setelah max retry
                }

                // 🔥 Exponential Backoff (0.5s, 1s, 1.5s, 2s, dst)
                usleep(500000 * $attempt);
            }
        }

        return $next($request);
    }
}
