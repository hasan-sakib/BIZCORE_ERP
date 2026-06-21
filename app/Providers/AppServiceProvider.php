<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Strict Eloquent mode in development
        Model::shouldBeStrict($this->app->isLocal());

        // Share auth user and locale to all views
        View::composer('*', function ($view) {
            $view->with('currentUser', Auth::user());
            $view->with('locale', app()->getLocale());
        });
    }
}
