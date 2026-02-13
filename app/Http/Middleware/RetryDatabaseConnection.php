<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RetryDatabaseConnection
{
    public function handle($request, Closure $next)
    {
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                // paksa test koneksi dulu
                DB::connection()->getPdo();
                break;
            } catch (\Exception $e) {
                $attempt++;
                sleep(2); // tunggu 2 detik sebelum retry

                if ($attempt >= $maxAttempts) {
                    throw $e; // kalau tetap gagal, lempar error
                }
            }
        }

        return $next($request);
    }
}
