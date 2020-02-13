<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Scope\Scope;
use Silber\Bouncer\Contracts\Scope as ScopeContract;

use Illuminate\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MultiTenancyTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * Reset any scopes that have been applied in a test.
     *
     * @return void
     */
    function tearDown(): void
    {
        Models::scope(new Scope);

        parent::tearDown();
    }

    /**
     * @test
     */
    function can_set_and_get_the_current_scope()
    {
        $bouncer = $this->bouncer();

        $this->assertNull($bouncer->scope()->get());

        $bouncer->scope()->to(1);
        $this->assertEquals(1, $bouncer->scope()->get());
    }

    /**
     * @test
     */
    function can_remove_the_current_scope()
    {
        $bouncer = $this->bouncer();

        $bouncer->scope()->to(1);
        $this->assertEquals(1, $bouncer->scope()->get());

        $bouncer->scope()->remove();
        $this->assertNull($bouncer->scope()->get());
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function creating_roles_and_abilities_automatically_scopes_them($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope()->to(1);

        $bouncer->allow('admin')->to('create', User::class);
        $bouncer->assign('admin')->to($user);

        $this->assertEquals(1, $bouncer->ability()->query()->value('scope'));
        $this->assertEquals(1, $bouncer->role()->query()->value('scope'));
        $this->assertEquals(1, $this->db()->table('permissions')->value('scope'));
        $this->assertEquals(1, $this->db()->table('assigned_roles')->value('scope'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function syncing_roles_is_properly_scoped($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope()->to(1);
        $bouncer->assign(['writer', 'reader'])->to($user);

        $bouncer->scope()->to(2);
        $bouncer->assign(['eraser', 'thinker'])->to($user);

        $bouncer->scope()->to(1);
        $bouncer->sync($user)->roles(['writer']);

        $this->assertTrue($bouncer->is($user)->a('writer'));
        $this->assertEquals(1, $user->roles()->count());

        $bouncer->scope()->to(2);
        $this->assertTrue($bouncer->is($user)->all('eraser', 'thinker'));
        $this->assertFalse($bouncer->is($user)->a('writer', 'reader'));

        $bouncer->sync($user)->roles(['thinker']);

        $this->assertTrue($bouncer->is($user)->a('thinker'));
        $this->assertEquals(1, $user->roles()->count());
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function syncing_abilities_is_properly_scoped($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope()->to(1);
        $bouncer->allow($user)->to(['write', 'read']);

        $bouncer->scope()->to(2);
        $bouncer->allow($user)->to(['erase', 'think']);

        $bouncer->scope()->to(1);
        $bouncer->sync($user)->abilities(['write', 'color']); // "read" is not deleted

        $this->assertTrue($bouncer->can('write'));
        $this->assertEquals(2, $user->abilities()->count());

        $bouncer->scope()->to(2);
        $this->assertTrue($bouncer->can('erase'));
        $this->assertTrue($bouncer->can('think'));
        $this->assertFalse($bouncer->can('write'));
        $this->assertFalse($bouncer->can('read'));

        $bouncer->sync($user)->abilities(['think']);

        $this->assertTrue($bouncer->can('think'));
        $this->assertEquals(1, $user->abilities()->count());
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function relation_queries_are_properly_scoped($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope()->to(1);
        $bouncer->allow($user)->to('create', User::class);

        $bouncer->scope()->to(2);
        $bouncer->allow($user)->to('delete', User::class);

        $bouncer->scope()->to(1);
        $abilities = $user->abilities()->get();

        $this->assertCount(1, $abilities);
        $this->assertEquals(1, $abilities->first()->scope);
        $this->assertEquals('create', $abilities->first()->name);
        $this->assertTrue($bouncer->can('create', User::class));
        $this->assertTrue($bouncer->cannot('delete', User::class));

        $bouncer->scope()->to(2);
        $abilities = $user->abilities()->get();

        $this->assertCount(1, $abilities);
        $this->assertEquals(2, $abilities->first()->scope);
        $this->assertEquals('delete', $abilities->first()->name);
        $this->assertTrue($bouncer->can('delete', User::class));
        $this->assertTrue($bouncer->cannot('create', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function relation_queries_can_be_scoped_exclusively($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope()->to(1)->onlyRelations();
        $bouncer->allow($user)->to('create', User::class);

        $bouncer->scope()->to(2);
        $bouncer->allow($user)->to('delete', User::class);

        $bouncer->scope()->to(1);
        $abilities = $user->abilities()->get();

        $this->assertCount(1, $abilities);
        $this->assertNull($abilities->first()->scope);
        $this->assertEquals('create', $abilities->first()->name);
        $this->assertTrue($bouncer->can('create', User::class));
        $this->assertTrue($bouncer->cannot('delete', User::class));

        $bouncer->scope()->to(2);
        $abilities = $user->abilities()->get();

        $this->assertCount(1, $abilities);
        $this->assertNull($abilities->first()->scope);
        $this->assertEquals('delete', $abilities->first()->name);
        $this->assertTrue($bouncer->can('delete', User::class));
        $this->assertTrue($bouncer->cannot('create', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function scoping_also_returns_global_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to('create', User::class);

        $bouncer->scope()->to(1)->onlyRelations();
        $bouncer->allow($user)->to('delete', User::class);

        $abilities = $user->abilities()->orderBy('id')->get();

        $this->assertCount(2, $abilities);
        $this->assertNull($abilities->first()->scope);
        $this->assertEquals('create', $abilities->first()->name);
        $this->assertTrue($bouncer->can('create', User::class));
        $this->assertTrue($bouncer->can('delete', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_abilities_only_affects_the_current_scope($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope()->to(1);
        $bouncer->allow($user)->to('create', User::class);

        $bouncer->scope()->to(2);
        $bouncer->allow($user)->to('create', User::class);
        $bouncer->forbid($user)->to('create', User::class);

        $bouncer->scope()->to(1);

        $this->assertTrue($bouncer->can('create', User::class));

        $bouncer->unforbid($user)->to('create', User::class);

        $bouncer->scope()->to(2);

        $this->assertTrue($bouncer->cannot('create', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function disallowing_abilities_only_affects_the_current_scope($provider)
    {
        list($bouncer, $user) = $provider();

        $admin = $bouncer->role()->create(['name' => 'admin']);
        $user->assign($admin);

        $bouncer->scope()->to(1)->onlyRelations();
        $admin->allow('create', User::class);

        $bouncer->scope()->to(2);
        $admin->allow('create', User::class);
        $admin->disallow('create', User::class);

        $bouncer->scope()->to(1);

        $this->assertTrue($bouncer->can('create', User::class));

        $bouncer->scope()->to(2);

        $this->assertTrue($bouncer->cannot('create', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function unforbidding_abilities_only_affects_the_current_scope($provider)
    {
        list($bouncer, $user) = $provider();

        $admin = $bouncer->role()->create(['name' => 'admin']);
        $user->assign($admin);

        $bouncer->scope()->to(1)->onlyRelations();
        $admin->allow()->everything();
        $admin->forbid()->to('create', User::class);

        $bouncer->scope()->to(2);
        $admin->allow()->everything();
        $admin->forbid()->to('create', User::class);
        $admin->unforbid()->to('create', User::class);

        $bouncer->scope()->to(1);

        $this->assertTrue($bouncer->cannot('create', User::class));

        $bouncer->scope()->to(2);

        $this->assertTrue($bouncer->can('create', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function assigning_and_retracting_roles_scopes_them_properly($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope()->to(1)->onlyRelations();
        $bouncer->assign('admin')->to($user);

        $bouncer->scope()->to(2);
        $bouncer->assign('admin')->to($user);
        $bouncer->retract('admin')->from($user);

        $bouncer->scope()->to(1);
        $this->assertTrue($bouncer->is($user)->an('admin'));

        $bouncer->scope()->to(2);
        $this->assertFalse($bouncer->is($user)->an('admin'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function role_abilities_can_be_excluded_from_scopes($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope()->to(1)
                ->onlyRelations()
                ->dontScopeRoleAbilities();

        $bouncer->allow('admin')->to('delete', User::class);

        $bouncer->scope()->to(2);

        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->can('delete', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_set_custom_scope($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->scope(new MultiTenancyNullScopeStub)->to(1);

        $bouncer->allow($user)->to('delete', User::class);

        $bouncer->scope()->to(2);

        $this->assertTrue($bouncer->can('delete', User::class));
    }

    /**
     * @test
     */
    function can_set_the_scope_temporarily()
    {
        $bouncer = $this->bouncer();

        $this->assertNull($bouncer->scope()->get());

        $result = $bouncer->scope()->onceTo(1, function () use ($bouncer) {
            $this->assertEquals(1, $bouncer->scope()->get());

            return 'result';
        });

        $this->assertEquals('result', $result);
        $this->assertNull($bouncer->scope()->get());
    }

    /**
     * @test
     */
    function can_remove_the_scope_temporarily()
    {
        $bouncer = $this->bouncer();

        $bouncer->scope()->to(1);

        $result = $bouncer->scope()->removeOnce(function () use ($bouncer) {
            $this->assertEquals(null, $bouncer->scope()->get());

            return 'result';
        });

        $this->assertEquals('result', $result);
        $this->assertEquals(1, $bouncer->scope()->get());
    }
}



class MultiTenancyNullScopeStub implements ScopeContract
{
    public function to()
    {
        //
    }

    public function appendToCacheKey($key)
    {
        return $key;
    }

    public function applyToModel(Model $model)
    {
        return $model;
    }

    public function applyToModelQuery($query, $table = null)
    {
        return $query;
    }

    public function applyToRelationQuery($query, $table)
    {
        return $query;
    }

    public function applyToRelation(BelongsToMany $relation)
    {
        return $relation;
    }

    public function get()
    {
        return null;
    }

    public function getAttachAttributes($authority = null)
    {
        return [];
    }

    public function onceTo($scope, callable $callback)
    {
        //
    }

    public function remove()
    {
        //
    }

    public function removeOnce(callable $callback)
    {
        //
    }
}
