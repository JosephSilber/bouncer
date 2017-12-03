<?php

require __DIR__.'/../migrations/create_bouncer_tables.php';

use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Seed\Seeder;
use Silber\Bouncer\CachedClipboard;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\HasRolesAndAbilities;

use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\Gate;
use Illuminate\Cache\ArrayStore;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class BaseTestCase extends TestCase
{
    /**
     * The clipboard instance.
     *
     * @var \Silber\Bouncer\CachedClipboard
     */
    protected $clipboard;

    /**
     * The database capsule instance.
     *
     * @var \Illuminate\Database\Capsule\Manager
     */
    protected $db;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected static $dispatcher;

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        Models::setUsersModel(User::class);

        $this->clipboard = new CachedClipboard(new ArrayStore);

        $this->migrate();
    }

    protected function migrate()
    {
        $this->db();

        (new CreateBouncerTables)->up();

        $this->migrateTestTables();
    }

    protected function migrateTestTables()
    {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->rollbackTestTables();

        (new CreateBouncerTables)->down();

        $this->clipboard = $this->db = null;
    }

    protected function rollbackTestTables()
    {
        Schema::drop('users');
        Schema::drop('accounts');
    }

    /**
     * Get a bouncer instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $user
     * @return \Silber\Bouncer\Bouncer
     */
    protected function bouncer(Eloquent $authority = null)
    {
        $bouncer = new Bouncer($this->clipboard);

        return $bouncer->setGate($this->gate($authority ?: User::create()));
    }

    /**
     * Get an access gate instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Auth\Access\Gate
     */
    protected function gate(Eloquent $authority)
    {
        $gate = new Gate(new Container, function () use ($authority) {
            return $authority;
        });

        $this->clipboard->registerAt($gate);

        return $gate;
    }

    /**
     * Get an instance of the database capsule manager.
     *
     * @return \Illuminate\Database\Capsule\Manager
     */
    protected function db()
    {
        if ($this->db) {
            return $this->db;
        }

        $this->db = new DB;

        $this->db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $this->db->bootEloquent();

        $this->db->setAsGlobal();

        Eloquent::setEventDispatcher($this->dispatcher());

        return $this->db;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Events\Dispatcher
     */
    protected function dispatcher()
    {
        if (is_null(static::$dispatcher)) {
            static::$dispatcher = new Dispatcher;
        }

        return static::$dispatcher;
    }
}

class User extends Eloquent
{
    use HasRolesAndAbilities;

    protected $table = 'users';

    protected $guarded = [];
}

class Account extends Eloquent
{
    use HasRolesAndAbilities;

    protected $table = 'accounts';

    protected $guarded = [];
}

class Schema
{
    public static function __callStatic($method, array $parameters)
    {
        $schema = DB::connection()->getSchemaBuilder();

        return call_user_func_array([$schema, $method], $parameters);
    }
}
