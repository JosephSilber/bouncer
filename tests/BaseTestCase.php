<?php

use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\HasRolesAndAbilities;

use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\ConnectionResolverInterface;

abstract class BaseTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        Eloquent::setConnectionResolver(new ConnectionResolver);
    }

    /**
     * Tear down Eloquent.
     *
     */
    public static function tearDownAfterClass()
    {
        Eloquent::unsetConnectionResolver();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        Role::$userModel = User::class;

        Ability::$userModel = User::class;

        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        $this->schema()->create('abilities', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type')->nullable();
            $table->timestamps();

            $table->unique(['title', 'entity_id', 'entity_type']);
        });

        $this->schema()->create('roles', function ($table) {
            $table->increments('id');
            $table->string('title')->unique();
            $table->timestamps();
        });

        $this->schema()->create('user_roles', function ($table) {
            $table->integer('role_id')->unsigned();
            $table->integer('user_id')->unsigned();
        });

        $this->schema()->create('user_abilities', function ($table) {
            $table->integer('ability_id')->unsigned();
            $table->integer('user_id')->unsigned();
        });

        $this->schema()->create('role_abilities', function ($table) {
            $table->integer('ability_id')->unsigned();
            $table->integer('role_id')->unsigned();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('users');
        $this->schema()->drop('abilities');
        $this->schema()->drop('roles');
        $this->schema()->drop('user_roles');
        $this->schema()->drop('user_abilities');
        $this->schema()->drop('role_abilities');
    }

    /**
     * Get a bouncer instance.
     *
     * @param  \User  $user
     * @return \Silber\Bouncer\Bouncer
     */
    protected function bouncer(User $user)
    {
        return (new Bouncer(new Clipboard))->setGate($this->gate($user));
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

        $gate->before(function ($user, $ability, $model = null, $additional = null) {
            if ( ! is_null($additional)) {
                return;
            }

            if ((new Clipboard)->check($user, $ability, $model)) {
                return true;
            }
        });

        return $gate;
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Schema\Builder
     */
    protected function schema()
    {
        return Eloquent::getConnectionResolver()->connection()->getSchemaBuilder();
    }
}

class User extends Eloquent
{
    use HasRolesAndAbilities;

    protected $table = 'users';
}

class ConnectionResolver implements ConnectionResolverInterface
{
    protected $connection;

    public function connection($name = null)
    {
        if (isset($this->connection)) {
            return $this->connection;
        }

        $pdo = new PDO('sqlite::memory:');

        return $this->connection = new SQLiteConnection($pdo);
    }

    public function getDefaultConnection()
    {
        return 'default';
    }

    public function setDefaultConnection($name)
    {
        //
    }
}

function app($type)
{
    $container = new Container;

    return $container->make($type);
}
