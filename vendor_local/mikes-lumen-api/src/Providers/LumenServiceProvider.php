<?php

namespace MikesLumenApi\Providers;

use Dingo\Api\Provider\LumenServiceProvider as ServiceProvider;

class LumenServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'mikelumenapi');
    }

    /**
     * Register the service providers
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->register(\MikesLumenApi\Providers\TranslationServiceProvider::class);

        $this->app->register(\MikesLumenApi\Providers\EventServiceProvider::class);

        $this->app->register(\MikesLumenApi\Providers\LogServiceProvider::class);

        $this->app->register(\MikesLumenApi\Providers\ExceptionServiceProvider::class);

        $this->registerPreflightPatch();
    }

    /**
     * This is to fix the CORS pre-flight OPTIONS requests on lumen.
     * https://gist.github.com/dragonfire1119/6ac56afa6f77710f4f66
     *
     * @return void
     */
    private function registerPreflightPatch()
    {
        $request = $this->app->make('request');
        if ($request->isMethod('OPTIONS')) {
            $this->app->options($request->path(), function () {
                return response('OK', 200);
            });
        }
    }
}
