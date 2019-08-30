<?php

namespace Silber\Bouncer;

use Illuminate\Support\Arr;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Console\CleanCommand;

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;

class BouncerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBouncer();
        $this->registerCommands();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerMorphs();
        $this->setTablePrefix();
        $this->setUserModel();

        $this->registerAtGate();

        if ($this->runningInConsole()) {
            $this->publishMiddleware();
            $this->publishMigrations();
        }
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    protected function registerBouncer()
    {
        $this->app->singleton(Bouncer::class, function () {
            return Bouncer::make()
                ->withClipboard(new CachedClipboard(new ArrayStore))
                ->withGate($this->app->make(Gate::class))
                ->create();
        });
    }

    /**
     * Register Bouncer's commands with artisan.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands(CleanCommand::class);
    }

    /**
     * Register Bouncer's models in the relation morph map.
     *
     * @return void
     */
    protected function registerMorphs()
    {
        Models::updateMorphMap();
    }

    /**
     * Set the table prefix for Bouncer's tables.
     *
     * @return void
     */
    protected function setTablePrefix()
    {
        if ($prefix = $this->getTablePrefix()) {
            Models::setPrefix($prefix);
        }
    }

    /**
     * Get the configured table prefix.
     *
     * @return string|null
     */
    protected function getTablePrefix()
    {
        $config = $this->app->config['database'];

        $connection = Arr::get($config, 'default');

        return Arr::get($config, "connections.{$connection}.prefix");
    }

    /**
     * Publish the package's middleware.
     *
     * @return void
     */
    protected function publishMiddleware()
    {
        $stub = __DIR__.'/../middleware/ScopeBouncer.php';

        $target = app_path('Http/Middleware/ScopeBouncer.php');

        $this->publishes([$stub => $target], 'bouncer.middleware');
    }

    /**
     * Publish the package's migrations.
     *
     * @return void
     */
    protected function publishMigrations()
    {
        if (class_exists('CreateBouncerTables')) {
            return;
        }

        $timestamp = date('Y_m_d_His', time());

        $stub = __DIR__.'/../migrations/create_bouncer_tables.php';

        $target = $this->app->databasePath().'/migrations/'.$timestamp.'_create_bouncer_tables.php';

        $this->publishes([$stub => $target], 'bouncer.migrations');
    }

    /**
     * Set the classname of the user model to be used by Bouncer.
     *
     * @return void
     */
    protected function setUserModel()
    {
        Models::setUsersModel($this->getUserModel());
    }

    /**
     * Get the user model from the application's auth config.
     *
     * @return string
     */
    protected function getUserModel()
    {
        $config = $this->app->make('config');

        if (! is_null($model = $this->getUserModelFromDefaultGuard($config))) {
            return $model;
        }

        return $config->get('auth.model', \App\User::class);
    }

    /**
     * Get the user model from the application's auth config.
     *
     * @param  \Illuminate\Config\Repository  $config
     * @return string|null
     */
    protected function getUserModelFromDefaultGuard($config)
    {
        if (is_null($guard = $config->get('auth.defaults.guard'))) {
            return null;
        }

        if (is_null($provider = $config->get("auth.guards.{$guard}.provider"))) {
            return null;
        }

        return $config->get("auth.providers.{$provider}.model");
    }

    /**
     * Register the bouncer's clipboard at the gate.
     *
     * @return void
     */
    protected function registerAtGate()
    {
        // When creating a Bouncer instance thru the Factory class, it'll
        // auto-register at the gate. We already registered Bouncer in
        // the container using the Factory, so now we'll resolve it.
        $this->app->make(Bouncer::class);
    }

    /**
     * Determine if we are running in the console.
     *
     * Copied from Laravel's Application class, since we need to support 5.1.
     *
     * @return bool
     */
    protected function runningInConsole()
    {
        return php_sapi_name() == 'cli' || php_sapi_name() == 'phpdbg';
    }
}
