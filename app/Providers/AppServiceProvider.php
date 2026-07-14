<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('partials.header', function ($view) {
            $timezone = DB::table('company_settings')
                ->where('company_id', config('worklive.company_id'))
                ->value('timezone') ?: 'America/Mexico_City';

            $view->with('corporateTimezone', in_array($timezone, timezone_identifiers_list(), true)
                ? $timezone
                : 'America/Mexico_City');
        });
    }
}
