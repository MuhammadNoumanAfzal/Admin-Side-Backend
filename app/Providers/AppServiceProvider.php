<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Paginator::useBootstrap();

        // ✅ IMPORTANT: don't hit DB during artisan/composer commands
        if (app()->runningInConsole()) {
            return;
        }

        // ✅ IMPORTANT: don't hit DB if table doesn't exist yet
        if (!Schema::hasTable('settings')) {
            return;
        }

        // ✅ Fetch all needed keys in a single query
        $keys = [
            'Social.youtube',
            'Social.facebook',
            'Social.twitter',
            'Social.linkedin',
            'Site.right',
        ];

        $settings = Setting::whereIn('key', $keys)->get()->keyBy('key');

        View::share('youtube',   $settings->get('Social.youtube'));
        View::share('facebook',  $settings->get('Social.facebook'));
        View::share('twitter',   $settings->get('Social.twitter'));
        View::share('linkedin',  $settings->get('Social.linkedin'));
        View::share('copyright', $settings->get('Site.right'));
    }
}
