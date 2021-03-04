<?php declare(strict_types=1);

namespace ORT\Interactive\Couchbase;

use Illuminate\Support\ServiceProvider;
use ORT\Interactive\Couchbase\Eloquent\Model;

class CouchbaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);
        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Add database driver.
        /*$this->app->singleton('couchbase.connection', function ($app) {
            $connectionName = config('database.connections.' . config('database.default'));
            return new Connection($connectionName);
        });
        $this->app->resolving('db', function ($db) {
            $db->extend('couchbase', function ($config) {
                return app('couchbase.connection');
            });
        });*/
        $registerSingletonForConnection = function(string $name, array $config = null) {
            static $registeredConnections;
            if(!isset($registeredConnections)) {
                $registeredConnections = [];
            }
            if(!isset($registeredConnections[$name])) {
                $config = $config ?? config('database.connections.' . $name);

                if (isset($config['driver']) && $config['driver'] === 'couchbase') {
                    $config['name'] = $name;
                    $config['database'] = $config['bucket'];
                    $this->app->singleton('couchbase.connection.' . $name, function ($app) use ($config) {
                        return new Connection($config);
                    });
                }

                $registeredConnections[$name] = true;
            }
        };

        $this->app->resolving('couchbase.connection', function()use(&$registerSingletonForConnection) {
            $name = config('database.default');
            $registerSingletonForConnection($name);
            return app('database.connection.'.$name);
        });

        $this->app->resolving('db', function ($db) use(&$registerSingletonForConnection) {
            $db->extend('couchbase', function ($config, $name) use(&$registerSingletonForConnection) {
                $registerSingletonForConnection($name, $config);
                return app('couchbase.connection.'.$name);
            });
        });
    }
}
