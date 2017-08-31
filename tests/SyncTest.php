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

        $this->assertTrue($bouncer->allows('edit-site'));
        $this->assertTrue($bouncer->allows('ban-users'));
        $this->assertTrue($bouncer->denies('access-dashboard'));

        $bouncer->sync($user)->abilities([$banUsers->id, 'access-dashboard']);

        $this->assertTrue($bouncer->denies('edit-site'));
        $this->assertTrue($bouncer->allows('ban-users'));
        $this->assertTrue($bouncer->allows('access-dashboard'));
    }

    public function test_syncing_forbidden_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->allow($user)->everything();
        $bouncer->forbid($user)->to([$editSite, $banUsers->id]);

        $this->assertTrue($bouncer->denies('edit-site'));
        $this->assertTrue($bouncer->denies('ban-users'));
        $this->assertTrue($bouncer->allows('access-dashboard'));

        $bouncer->sync($user)->forbiddenAbilities([$banUsers->id, 'access-dashboard']);

        $this->assertTrue($bouncer->allows('edit-site'));
        $this->assertTrue($bouncer->denies('ban-users'));
        $this->assertTrue($bouncer->denies('access-dashboard'));
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
