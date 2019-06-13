<?php

namespace Sh\Providers;

use Sh\Services\ImageUploadService;
use Illuminate\Support\ServiceProvider;

class ImageUploadProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('imageUpload', ImageUploadService::class);
    }
}
