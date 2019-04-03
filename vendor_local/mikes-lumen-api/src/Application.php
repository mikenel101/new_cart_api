<?php

namespace MikesLumenApi;

use Laravel\Lumen\Application as LumenApplication;

class Application extends LumenApplication
{
    /**
     * Prepare the application to execute a console command.
     *
     * @param  bool  $aliases
     * @return void
     */
    public function prepareForConsoleCommand($aliases = true)
    {
        $this->withFacades($aliases);
        $this->make('cache');
        $this->make('queue');
        $this->configure('database');
        $this->register(\MikesLumenApi\Providers\MigrationServiceProvider::class);
        $this->register('Illuminate\Database\SeedServiceProvider');
        $this->register('Illuminate\Queue\ConsoleServiceProvider');
    }
}
