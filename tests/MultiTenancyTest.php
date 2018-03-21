<?php

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
    /**
     * Reset any scopes that have been applied in a test.
     *
     * @return void
     */
    public function tearDown()
    {
        Models::scope(new Scope);

        parent::tearDown();
    }

    public function test_creating_roles_and_abilities_automatically_scopes_them()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->scope()->to(1);

        $bouncer->allow('admin')->to('create', User::class);
        $bouncer->assign('admin')->to($user);

        $this->assertEquals(1, $bouncer->ability()->query()->value('scope'));
        $this->assertEquals(1, $bouncer->role()->query()->value('scope'));
        $this->assertEquals(1, $this->db()->table('permissions')->value('scope'));
        $this->assertEquals(1, $this->db()->table('assigned_roles')->value('scope'));
    }

    public function test_syncing_roles_is_properly_scoped()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

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

    public function test_syncing_abilities_is_properly_scoped()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

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

    public function test_relation_queries_are_properly_scoped()
    {
        $bouncer = $this->bouncer($user = User::create());

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

    public function test_relation_queries_can_be_scoped_exclusively()
    {
        $bouncer = $this->bouncer($user = User::create());

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

    public function test_scoping_also_returns_global_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create', User::class);

        $bouncer->scope()->to(1)->onlyRelations();
        $bouncer->allow($user)->to('delete', User::class);

        $abilities = $user->abilities()->get();

        $this->assertCount(2, $abilities);
        $this->assertNull($abilities->first()->scope);
        $this->assertEquals('create', $abilities->first()->name);
        $this->assertTrue($bouncer->can('create', User::class));
        $this->assertTrue($bouncer->can('delete', User::class));
    }

    public function test_forbidding_abilities_only_affects_the_current_scope()
    {
        $bouncer = $this->bouncer($user = User::create());

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

    public function test_assigning_and_retracting_roles_scopes_them_properly()
    {
        $bouncer = $this->bouncer($user = User::create());

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

    public function test_role_abilities_can_be_excluded_from_scopes()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->scope()->to(1)
                ->onlyRelations()
                ->dontScopeRoleAbilities();

        $bouncer->allow('admin')->to('delete', User::class);

        $bouncer->scope()->to(2);

        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->can('delete', User::class));
    }

    public function test_can_set_custom_scope()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->scope(new MultiTenancyNullScopeStub)->to(1);

        $bouncer->allow($user)->to('delete', User::class);

        $bouncer->scope()->to(2);

        $this->assertTrue($bouncer->can('delete', User::class));
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

    public function applyToModelQuery($query, $table)
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

    public function getAttachAttributes($authority = null)
    {
        return [];
    }
}
