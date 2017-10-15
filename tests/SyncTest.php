<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class SyncTest extends BaseTestCase
{
    public function test_syncing_roles()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $admin      = $this->role('admin');
        $editor     = $this->role('editor');
        $reviewer   = $this->role('reviewer');
        $subscriber = $this->role('subscriber');

        $user->assign([$admin, $editor]);

        $this->assertTrue($bouncer->is($user)->all($admin, $editor));

        $bouncer->sync($user)->roles([$editor->id, $reviewer->name, $subscriber]);

        $this->assertTrue($bouncer->is($user)->all($editor, $reviewer, $subscriber));
        $this->assertTrue($bouncer->is($user)->notAn($admin));
    }

    public function test_syncing_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->allow($user)->to([$editSite, $banUsers]);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));

        $bouncer->sync($user)->abilities([$banUsers->id, 'access-dashboard']);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));
    }

    public function test_syncing_abilities_With_a_map()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $deleteUser = Ability::createForModel($user, 'delete');
        $createAccounts = Ability::createForModel(Account::class, 'create');

        $bouncer->allow($user)->to([$deleteUser, $createAccounts]);

        $this->assertTrue($bouncer->can('delete', $user));
        $this->assertTrue($bouncer->can('create', Account::class));

        $bouncer->sync($user)->abilities([
            'access-dashboard',
            'create' => Account::class,
            'view' => $user,
        ]);

        $this->assertTrue($bouncer->cannot('delete', $user));
        $this->assertTrue($bouncer->cannot('view', User::class));
        $this->assertTrue($bouncer->can('create', Account::class));
        $this->assertTrue($bouncer->can('view', $user));
        $this->assertTrue($bouncer->can('access-dashboard'));
    }

    public function test_syncing_forbidden_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->allow($user)->everything();
        $bouncer->forbid($user)->to([$editSite, $banUsers->id]);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->cannot('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));

        $bouncer->sync($user)->forbiddenAbilities([$banUsers->id, 'access-dashboard']);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->cannot('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));
    }

    public function test_syncing_a_roles_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->assign('admin')->to($user);
        $bouncer->allow('admin')->to([$editSite, $banUsers]);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));

        $bouncer->sync('admin')->abilities([$banUsers->id, 'access-dashboard']);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));
    }

    /**
     * Create a new role with the given name.
     *
     * @param  string  $name
     * @return \Silber\Bouncer\Database\Role
     */
    protected function role($name)
    {
        return Role::create(compact('name'));
    }
}
