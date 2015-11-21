<?php

use Illuminate\Database\Schema\Builder;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\CachedClipboard;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\HasRolesAndAbilities;

use Illuminate\Auth\Access\Gate;
use Illuminate\Cache\ArrayStore;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;


abstract class BaseOverrideTestCase extends PHPUnit_Framework_TestCase
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
        Role::$userModel = MyUser::class;

        Ability::$userModel = MyUser::class;

        $this->schema()->create('user', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        $this->schema()->create('ability', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type')->nullable();
            $table->timestamps();

            $table->unique(['name', 'entity_id', 'entity_type']);
        });

        $this->schema()->create('role', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        $this->schema()->create('user_role', function ($table) {
            $table->integer('id_user')->unsigned();
            $table->integer('id_role')->unsigned();

            $table->unique(['id_role', 'id_user']);

            $table->foreign('id_role')->references('id')->on('role');
            $table->foreign('id_user')->references('id')->on('user');
        });

        $this->schema()->create('user_ability', function ($table) {
            $table->integer('id_ability')->unsigned();
            $table->integer('id_user')->unsigned();

            $table->unique(['id_ability', 'id_user']);

            $table->foreign('id_ability')->references('id')->on('ability');
            $table->foreign('id_user')->references('id')->on('user');
        });

        $this->schema()->create('role_ability', function ($table) {
            $table->integer('id_ability')->unsigned();
            $table->integer('id_role')->unsigned();

            $table->unique(['id_ability', 'id_role']);

            $table->foreign('id_ability')->references('id')->on('ability');
            $table->foreign('id_role')->references('id')->on('role');
        });

        $container = $this->getContainer();
        $container->singleton(
            Clipboard::class,
            function () {
                return new CachedClipboard(new ArrayStore, MyRole::class, MyAbility::class);
            }
        );
        $this->clipboard = $container->make(Clipboard::class);
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        $container = Container::getInstance() ?: new Container;
        Container::setInstance($container);
        return $container;
    }

    /**
     * Get a schema builder instance.
     *
     * @return Builder
     */
    protected function schema()
    {
        return $this->db()->connection()->getSchemaBuilder();
    }

    protected function db()
    {
        if ($this->db) {
            return $this->db;
        }

        $this->db = new DB;

        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->db->bootEloquent();

        $this->db->setAsGlobal();

        return $this->db;
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('role_ability');
        $this->schema()->drop('user_ability');
        $this->schema()->drop('user_role');
        $this->schema()->drop('role');
        $this->schema()->drop('ability');
        $this->schema()->drop('user');

        $this->clipboard = $this->db = null;
    }

    /**
     * Get a bouncer instance.
     *
     * @param MyUser|User $user
     * @return Bouncer
     */
    protected function bouncer(MyUser $user)
    {
        $container = $this->getContainer();

        $container->singleton(
            Bouncer::class,
            function () {
                return new Bouncer($this->clipboard, MyRole::class, MyAbility::class);
            }
        );

        return $container->make(Bouncer::class)->setGate($this->gate($user));
    }

    /**
     * Get an access gate instance.
     *
     * @param MyUser|User $user
     * @return Gate
     */
    protected function gate(MyUser $user)
    {
        $gate = new Gate(new Container, function () use ($user) {
            return $user;
        });

        $this->clipboard->registerAt($gate);

        return $gate;
    }
}

class MyUser extends Eloquent
{
    use HasRolesAndAbilities;

    protected $table = 'user';

    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            MyRole::class,
            'user_role',
            'id_user',
            'id_role'
        );
    }

    /**
     * The Abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function abilities()
    {
        return $this->belongsToMany(
            MyAbility::class,
            'user_ability',
            'id_user',
            'id_ability'
        );
    }
}

class MyRole extends Role
{
    protected $table = 'role';

    /**
     * The abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function abilities()
    {
        return $this->belongsToMany(
            MyAbility::class,
            'role_ability',
            'id_role',
            'id_ability'
        );
    }

    /**
     * The users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            MyUser::class,
            'user_role',
            'id_role',
            'id_user'
        );
    }
}

class MyAbility extends Ability
{
    protected $table = 'ability';

    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            MyRole::class,
            'role_ability',
            'id_ability',
            'id_role'
        );
    }

    /**
     * The users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            MyUser::class,
            'user_ability',
            'id_ability',
            'id_user'
        );
    }

}