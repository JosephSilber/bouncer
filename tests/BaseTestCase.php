<?php

require __DIR__.'/../migrations/create_bouncer_tables.php';

use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Seed\Seeder;
use Silber\Bouncer\CachedClipboard;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\HasRolesAndAbilities;

use Illuminate\Auth\Access\Gate;
use Illuminate\Cache\ArrayStore;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class BaseTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * The database capsule instance.
     *
     * @var \Illuminate\Database\Capsule\Manager
     */
    protected $db;

    /**
     * The clipboard instance.
     *
     * @var \Silber\Bouncer\CachedClipboard
     */
    protected $clipboard;

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

        Schema::create('users', function ($table) {
            $table->increments('id');
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
        Schema::drop('users');

        (new CreateBouncerTables)->down();

        $this->clipboard = $this->db = null;
    }

    /**
     * Get a bouncer instance.
     *
     * @param  \User|null  $user
     * @return \Silber\Bouncer\Bouncer
     */
    protected function bouncer(User $user = null)
    {
        $bouncer = new Bouncer($this->clipboard, new Seeder(new Container));

        return $bouncer->setGate($this->gate($user ?: User::create()));
    }

    /**
     * Get an access gate instance.
     *
     * @param  \User  $user
     * @return \Illuminate\Auth\Access\Gate
     */
    protected function gate(User $user)
    {
        $gate = new Gate(new Container, function () use ($user) {
            return $user;
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

        return $this->db;
    }
}

class User extends Eloquent
{
    use HasRolesAndAbilities;

    protected $table = 'users';

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
