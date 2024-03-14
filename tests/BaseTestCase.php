<?php

namespace Silber\Bouncer\Tests;

use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Contracts\Clipboard as ClipboardContract;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Guard;
use Workbench\App\Models\User;

use function Orchestra\Testbench\artisan;
use function Orchestra\Testbench\package_path;
use function Orchestra\Testbench\workbench_path;

abstract class BaseTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup the world for the tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        Models::setUsersModel(User::class);

        static::registerClipboard();
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        artisan($this, 'migrate:install');

        $this->loadMigrationsFrom(package_path('migrations'));
        $this->loadMigrationsFrom(workbench_path('database/migrations'));
    }

    /**
     * Register the clipboard with the container.
     */
    protected static function registerClipboard(): void
    {
        Container::getInstance()
            ->instance(ClipboardContract::class, static::makeClipboard());
    }

    /**
     * Make a new clipboard with the container.
     */
    protected static function makeClipboard(): ClipboardContract
    {
        return new Clipboard;
    }

    /**
     * Get a bouncer instance.
     */
    protected static function bouncer(?Model $authority = null): Bouncer
    {
        $gate = static::gate($authority ?: User::create());

        $clipboard = Container::getInstance()->make(ClipboardContract::class);

        $bouncer = new Bouncer((new Guard($clipboard))->registerAt($gate));

        return $bouncer->setGate($gate);
    }

    /**
     * Get an access gate instance.
     */
    protected static function gate(Model $authority): Gate
    {
        return new Gate(Container::getInstance(), fn () => $authority);
    }

    /**
     * Get the Clipboard instance from the container.
     */
    protected function clipboard(): ClipboardContract
    {
        return Container::getInstance()->make(ClipboardContract::class);
    }

    /**
     * Get the DB manager instance from the container.
     */
    protected function db(): DatabaseManager
    {
        return Container::getInstance()->make('db');
    }
}
