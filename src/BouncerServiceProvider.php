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
            $bouncer = new Bouncer($this->app->make(Clipboard::class));

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
    protected function setUserModel()
    {
        $model = $this->app->make('config')->get('auth.model');

        Ability::$userModel = $model;

        Role::$userModel = $model;
    }
}
