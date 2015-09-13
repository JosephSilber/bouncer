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

class BouncerTest extends PHPUnit_Framework_TestCase
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
            $table->string('title')->unique();
            $table->timestamps();
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

    public function test_bouncer_can_give_and_remove_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('edit-site');

        $this->assertTrue($bouncer->allows('edit-site'));

        $bouncer->disallow($user)->to('edit-site');

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_bouncer_can_give_and_remove_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->allows('edit-site'));

        $bouncer->retract('admin')->from($user);

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_bouncer_can_disallow_abilities_on_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->disallow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_trait_list_abilities_gets_all_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->allow($user)->to('create-posts');
        $bouncer->allow('editor')->to('edit-posts');
        $bouncer->assign('admin')->to($user);

        $this->assertEquals(['create-posts', 'edit-site'], $user->listAbilities()->sort()->all());
    }

    public function test_trait_can_give_and_remove_abilities()
    {
        $gate = $this->gate($user = User::create());

        $user->allow('edit-site');

        $this->assertTrue($gate->allows('edit-site'));

        $user->disallow('edit-site');

        $this->assertTrue($gate->denies('edit-site'));
    }

    public function test_trait_can_assign_and_retract_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('edit-site');
        $user->assign('admin');

        $this->assertTrue($bouncer->allows('edit-site'));

        $user->retract('admin');

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    /**
     * Get a bouncer instance.
     *
     * @param  \User  $user
     * @return \Silber\Bouncer\Bouncer
     */
    protected function bouncer(User $user)
    {
        return (new Bouncer)->setGate($this->gate($user));
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

        $gate->before(function ($user, $ability) {
            if ((new Clipboard)->check($user, $ability)) {
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
