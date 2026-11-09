<?php

namespace App\Providers;

use App\Models\Siswa;
use App\Policies\SiswaPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        Carbon::setLocale(config('app.locale'));

        // Paksa HTTPS jika di environment production (misal: Railway)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Daftarkan SiswaPolicy — otorisasi data siswa & mapel di semua controller
        Gate::policy(Siswa::class, SiswaPolicy::class);
    }
}
