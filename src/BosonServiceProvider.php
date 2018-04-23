<?php

namespace Intralix\Boson;

use Illuminate\Support\ServiceProvider;
use Intralix\Boson\Boson;


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
        $this->mergeConfigFrom(__DIR__ . '/../config/boson.php', 'boson');
        
        // Bind 
        $this->app->singleton('boson', function ($app) {
            return new Boson(config('boson'));
        });  
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Boson'];
    }     

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/    
    private function handleConfigs() {

        $configPath = __DIR__ . '/../config/boson.php';
        $this->publishes([ $configPath => config_path('boson.php') ]);                
    }        

}
