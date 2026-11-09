<?php

namespace App\Providers;

use App\Support\Menu;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Nama hari/bulan berbahasa Indonesia (translatedFormat, diffForHumans)
        Carbon::setLocale(config('app.locale'));

        // Data menu untuk layout utama (sidebar + judul halaman)
        View::composer('layouts.app', function ($view) {
            $view->with([
                'menuGroups' => Menu::forUser(auth()->user()),
                'currentMenu' => Menu::current(),
            ]);
        });
    }
}
