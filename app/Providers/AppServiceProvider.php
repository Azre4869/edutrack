<?php

namespace App\Providers;

use App\Models\Siswa;
use App\Policies\SiswaPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
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

        // Daftarkan SiswaPolicy — otorisasi data siswa & mapel di semua controller
        Gate::policy(Siswa::class, SiswaPolicy::class);
    }
}
