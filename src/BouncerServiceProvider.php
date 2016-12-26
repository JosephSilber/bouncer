<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Seed\Seeder;
use Silber\Bouncer\UpgradeCommand;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Seed\SeedCommand;

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
        $this->registerClipboard();
        $this->registerCommands();
        $this->registerBouncer();
        $this->registerMorphs();
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
        $this->setTablePrefix();
        $this->setUserModel();
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

        $connection = array_get($config, 'default');

        return array_get($config, "connections.{$connection}.prefix");
    }

    /**
     * Register Bouncer's commands with artisan.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands(SeedCommand::class);
        $this->commands(UpgradeCommand::class);
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
            $bouncer = new Bouncer($this->app->make(Clipboard::class));

            return $bouncer->setGate($this->app->make(Gate::class));
        });
    }

    /**
     * Register Bouncer's models in the relation morph map.
     *
     * @return void
     */
    protected function registerMorphs()
    {
        Relation::morphMap([
            \Silber\Bouncer\Database\Role::class,
            \Silber\Bouncer\Database\Ability::class,
        ]);
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

        $this->publishes([$stub => $target], 'bouncer.migrations');
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
