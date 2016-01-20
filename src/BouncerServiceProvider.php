<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Seed\Seeder;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Seed\SeedCommand;

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;

class BouncerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSeedCommand();
        $this->registerClipboard();
        $this->registerBouncer();
        $this->registerSeeder();
    }

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
     * Register the seed command with artisan.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->commands(SeedCommand::class);
    }

    /**
     * Register the cache clipboard as a singleton.
     *
     * @return void
     */
    protected function registerClipboard()
    {
        $this->app->singleton(Clipboard::class, function () {
            return new CachedClipboard(new ArrayStore);
        });
    }

    /**
     * Register the bouncer as a singleton.
     *
     * @return void
     */
    protected function registerBouncer()
    {
        $this->app->singleton(Bouncer::class, function () {
            $bouncer = new Bouncer(
                $this->app->make(Clipboard::class),
                $this->app->make(Seeder::class)
            );

            return $bouncer->setGate($this->app->make(Gate::class));
        });
    }

    /**
     * Register the seeder as a singleton.
     *
     * @return void
     */
    protected function registerSeeder()
    {
        $this->app->singleton(Seeder::class);
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
     * Set the classname of the user model to be used by Bouncer.
     *
     * @return void
     */
    protected function setUserModel()
    {
        $config = $this->app->make('config');

        $model = $config->get('auth.providers.users.model', function () use ($config) {
            return $config->get('auth.model', \App\User::class);
        });

        Models::setUsersModel($model);

        Models::setTables([
            'users' => Models::user()->getTable(),
        ]);
    }
}
