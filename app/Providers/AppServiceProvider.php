<?php

namespace SevereHeadache\Coffre\Providers;

use Illuminate\Support\ServiceProvider;
use SevereHeadache\Coffre\Services\Storage\StorageFactory;
use SevereHeadache\Coffre\Services\Storage\StorageInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StorageInterface::class, function () {
            return StorageFactory::create(config('app.driver', ''));
        });
    }
}
