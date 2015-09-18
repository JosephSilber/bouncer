<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;
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
        $this->publishMigrations();
        $this->registerAtGate();
        $this->setUserModel();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Clipboard::class);

        $this->app->bind(Bouncer::class, function () {
            return (new Bouncer)->setGate($this->app->make(Gate::class));
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
     * Register the bouncer's clipboard at the gate.
     *
     * @return void
     */
    protected function registerAtGate()
    {
        $gate = $this->app->make(Gate::class);

        $gate->before(function ($user, $ability, $model = null, $additional = null) {
            if ( ! is_null($additional)) {
                return;
            }

            if ($this->app->make(Clipboard::class)->check($user, $ability)) {
                return true;
            }
        });
    }

    /**
     * Set the name of the user model on the role and Ability classes.
     *
     * @return void
     */
    protected function setUserModel()
    {
        $model = $this->app->make('config')->get('auth.model');

        Ability::$userModel = $model;

        Role::$userModel = $model;
    }
}
