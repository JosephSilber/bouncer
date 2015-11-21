<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;

class BouncerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerAtGate();
        $this->setModelOverrides();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $roleModelClass = config('bouncer.role', 'Silber\Bouncer\Database\Role');
        $abilityModelClass = config('bouncer.ability', 'Silber\Bouncer\Database\Ability');

        $this->app->singleton(Clipboard::class, function () use ($roleModelClass, $abilityModelClass) {
            return new CachedClipboard(
                new ArrayStore(),
                $roleModelClass,
                $abilityModelClass
            );
        });

        $this->app->bind(Bouncer::class, function () use ($roleModelClass, $abilityModelClass) {
            $bouncer = new Bouncer(
                $this->app->make(Clipboard::class),
                $roleModelClass,
                $abilityModelClass
            );

            return $bouncer->setGate($this->app->make(Gate::class));
        });
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

        $this->publishes([$stub => $target], 'migrations');
    }

    /**
     * Publish the config file to the application config directory
     */
    public function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../../config/bouncer.php' => config_path('bouncer.php'),
        ], 'config');
    }

    /**
     * Register the bouncer's clipboard at the gate.
     *
     * @return void
     */
    protected function registerAtGate()
    {
        $gate = $this->app->make(Gate::class);

        $clipboard = $this->app->make(Clipboard::class);

        $clipboard->registerAt($gate);
    }

    /**
     * Set the name of the user model on the role and Ability classes.
     *
     * @return void
     */
    protected function setModelOverrides()
    {
        $model = $this->app->make('config')->get('auth.model');

        Ability::$userModel = $model;

        Role::$userModel = $model;

        $roleModelClass = config('bouncer.role', 'Silber\Bouncer\Database\Role');
        Role::$overrideModelClass = $roleModelClass;

        $abilityModelClass = config('bouncer.ability', 'Silber\Bouncer\Database\Ability');
        Ability::$overrideModelClass = $abilityModelClass;
    }
}
