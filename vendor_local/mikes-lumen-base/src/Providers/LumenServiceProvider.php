<?php

namespace MikesLumenBase\Providers;

use Prettus\Repository\Providers\LumenRepositoryServiceProvider as ServiceProvider;
use MikesLumenBase\Utils\Fetcher;
use MikesLumenBase\Utils\Publisher;

class LumenServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'mikelumenbase');
    }

    /**
     * Register the service providers
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->bind('fetcher', function () {
            return new Fetcher();
        });

        $this->app->bind('publisher', function () {
            return new Publisher();
        });
    }
}
