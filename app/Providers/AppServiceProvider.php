<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // === FORCE HTTPS DI PRODUCTION ===
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // === VARIABEL GLOBAL UNTUK VIEW ===
        View::composer('*', function ($view) {
            $today = Carbon::today()->toDateString();

            $hasTodayAgenda = CalendarEvent::where('date', $today)
                ->whereNotNull('agenda') // hanya yang ada agenda
                ->exists();

            $view->with('hasTodayAgenda', $hasTodayAgenda);
        });
    }
}
