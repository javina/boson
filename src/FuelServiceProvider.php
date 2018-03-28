<?php

namespace Intralix\Fuel;

use Illuminate\Support\ServiceProvider;
use Intralix\Fuel\Fuel;


class FuelServiceProvider extends ServiceProvider
{
/**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->handleConfigs();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
         // Load config File
        $this->mergeConfigFrom(__DIR__ . '/../config/fuel.php', 'fuel');
        // Bind 
        $this->app->singleton('fuel', function ($app) {
            return new Fuel(config('fuel'));
        });  
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Fuel'];
    }     

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/    
    private function handleConfigs() {

        $configPath = __DIR__ . '/../config/fuel.php';
        $this->publishes([ $configPath => config_path('fuel.php') ]);                
    }        

}
