<?php

    namespace Aireset\Zoop\Providers;

    use Illuminate\Support\ServiceProvider;
    use Aireset\Zoop\Zoop;
    use Aireset\Zoop\ZoopCard;

    class ZoopServiceProvider extends ServiceProvider
    {
        public function register()
        {
            $this->app->bind('zoop', function ($app) {
                return new Zoop($app['log'], $app['validator']);
            });
            $this->app->bind('zoop_card', function ($app) {
                return new ZoopCard($app['log'], $app['validator']);
            });
        }

        public function boot()
        {
            // if (!method_exists($this->app, 'routesAreCached')) {
            //     require __DIR__ . '../routes.php';
            //
            //     return; // lumen
            // }
            //
            // if (!$this->app->routesAreCached()) {
            //     require __DIR__ . '../routes.php';
            // }

            $this->publishes([
                __DIR__ . '../Config.php' => config_path('zoop.php'),
            ],
                'config');
        }
    }
