<?php

namespace App\Providers\ProgressiveSeeders;

use App\Providers\ProgressiveSeeders\Console\Commands\ProgressiveSeederCommand;
use App\Providers\ProgressiveSeeders\Console\Commands\ProgressiveSeederRollbackCommand;
use Illuminate\Support\ServiceProvider;

class ProgressiveSeedersProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    ProgressiveSeederCommand::class,
                    ProgressiveSeederRollbackCommand::class,
                ]
            );
        }
    }

    public function register()
    {
        //
    }
}
