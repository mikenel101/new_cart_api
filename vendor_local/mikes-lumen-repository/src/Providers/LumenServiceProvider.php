<?php

namespace MikesLumenRepository\Providers;

use Prettus\Repository\Providers\LumenRepositoryServiceProvider as ServiceProvider;

class LumenServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'mikelumenrepository');
    }


    /**
     * Register the service providers
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->register(\MikesLumenRepository\Providers\ValidatorServiceProvider::class);
    }
}
