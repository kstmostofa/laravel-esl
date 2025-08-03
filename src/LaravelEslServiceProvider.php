<?php

namespace Kstmostofa\LaravelEsl;

use Illuminate\Support\ServiceProvider;
use Kstmostofa\LaravelEsl\Facades\Esl as EslFacade;

class LaravelEslServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/esl.php' => config_path('esl.php'),
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/esl.php', 'esl'
        );

        $this->app->bind(EslConnection::class, function ($app) {
            return new EslConnection(
                $app['config']['esl.host'],
                $app['config']['esl.port'],
                $app['config']['esl.password']
            );
        });

        $this->app->alias(EslConnection::class, 'esl');
        $this->app->alias(EslFacade::class, 'Esl');
    }
}
