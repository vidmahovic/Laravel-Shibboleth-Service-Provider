<?php namespace StudentAffairsUwm\Shibboleth;

use Illuminate\Support\ServiceProvider;

class ShibbolethServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['auth']->extend('shibboleth', function ($app) {
            return new Providers\ShibbolethUserProvider($this->app['hash'], $this->app['config']['auth.model']);
        });

        // Publish the configuration, migrations, and User / Group models
        $this->publishes([
            __DIR__ . '/../../config/shibboleth.php' => config_path('shibboleth.php'),
        ]);

        include __DIR__ . '/../../routes.php';
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

}
