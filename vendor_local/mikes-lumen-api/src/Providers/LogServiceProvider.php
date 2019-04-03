<?php

namespace MikesLumenApi\Providers;

use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\LineFormatter;
// use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogServiceProvider extends ServiceProvider
{
    // const MAX_FILE = 7;

    /**
     * The number of days which system rotates logs
     *
     * @var string
     */
    // private $logRotationDay;

    /**
     * Log Level
     *
     * @var int
     */
    private $logLevel;

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;

        // $this->logRotationDay = getenv('LOG_ROTATION_DAY') * 1 > 0 ? getenv('LOG_ROTATION_DAY') : self::MAX_FILE;

        if (getenv('APP_ENV') === 'production') {
            $this->logLevel = Logger::INFO;
        } else {
            $this->logLevel = Logger::DEBUG;
        }
    }

    /**
     * Configure logging on boot.
     *
     * @return void
     */
    public function boot()
    {
        if (getenv('APP_ENV') == 'testing') {
            $handlers[] = (new StreamHandler(storage_path('logs/unittest.log'), $this->logLevel))
                ->setFormatter(new LineFormatter(null, null, true, true));
        } else {
            $handlers[] = (new StreamHandler('php://stdout', $this->logLevel))
                ->setFormatter(new LineFormatter(null, null, true, true));
        }
        // $handlers[] = (new RotatingFileHandler(storage_path('logs/api_log.log'), $this->logRotationDay, $this->logLevel))
        //    ->setFormatter(new LineFormatter(null, null, true, true));

        $this->app['log']->setHandlers($handlers);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
