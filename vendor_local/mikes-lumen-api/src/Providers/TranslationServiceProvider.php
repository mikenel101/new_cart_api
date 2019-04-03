<?php

namespace MikesLumenApi\Providers;

use Illuminate\Translation\TranslationServiceProvider as ServiceProvider;
use MikesLumenApi\Translation\FileLoader;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->locale = getenv('APP_LOCALE');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // For https://github.com/andersao/l5-repository/issues/28
        $this->app->bind(\Symfony\Component\Translation\TranslatorInterface::class, function ($app) {
            return $app['translator'];
        });

        if ($this->locale) {
            app('translator')->setLocale($this->locale);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // For making $this->app->singleton('translation.loader', ...) work
        unset($this->app->availableBindings['translator']);

        parent::register();
    }

    /**
    * Register the translation line loader.
     *
     * @return void
    */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            $this->app['path.lang'] = base_path('resources/lang/');
            return new FileLoader($app['files'], $app['path.lang'], [__DIR__ . '/../resources/default-lang']);
        });
    }
}
