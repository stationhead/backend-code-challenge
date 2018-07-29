<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Aws\Sns\SnsClient;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Register the default Serializer for Fractal.
         $this->app->bind('League\Fractal\Manager', function($app) {
            $manager = new \League\Fractal\Manager;
            $manager->setSerializer(new \App\Transformers\Serializers\SimpleArraySerializer);

            return $manager;
        });

        // Register a Singleton of the STHResponse.
        $this->app->singleton('App\Responses\STHResponse', function($app){
            return new \App\Responses\STHResponse(
                $this->app->make('League\Fractal\Manager')
            );
        });

    }
}
