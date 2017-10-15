<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class BouncerSimpleTest extends BaseTestCase
{
    public function test_bouncer_can_give_and_remove_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->allow($user)->to('edit-site');
        $bouncer->allow($user)->to([$banUsers, $accessDashboard->id]);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));

        $bouncer->disallow($user)->to($editSite);
        $bouncer->disallow($user)->to('ban-users');
        $bouncer->disallow($user)->to($accessDashboard->id);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->cannot('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));
    }

    public function test_bouncer_can_give_and_remove_wildcard_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('*'));

        $bouncer->disallow($user)->to('*');

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    public function test_bouncer_can_ignore_duplicate_ability_allowances()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('ban-users');
        $bouncer->allow($user1)->to('ban-users');

        $bouncer->allow($user1)->to('ban', $user2);
        $bouncer->allow($user1)->to('ban', $user2);

        $this->assertCount(2, $user1->abilities);

        $admin = $bouncer->role(['name' => 'admin']);
        $admin->save();

        $bouncer->allow($admin)->to('ban-users');
        $bouncer->allow($admin)->to('ban-users');

        $bouncer->allow($admin)->to('ban', $user1);
        $bouncer->allow($admin)->to('ban', $user1);

        $this->assertCount(2, $admin->abilities);
    }

    public function test_bouncer_can_give_and_remove_roles()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow('admin')->to('ban-users');
        $bouncer->assign('admin')->to($user);

        $editor = $bouncer->role()->create(['name' => 'editor']);
        $bouncer->allow($editor)->to('ban-users');
        $bouncer->assign($editor)->to($user);

        $this->assertTrue($bouncer->can('ban-users'));

        $bouncer->retract('admin')->from($user);
        $bouncer->retract($editor)->from($user);

        $this->assertTrue($bouncer->cannot('ban-users'));
    }

    public function test_bouncer_can_give_and_remove_multiple_roles_at_once()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $admin    = $this->role('admin');
        $editor   = $this->role('editor');
        $reviewer = $this->role('reviewer');

        $bouncer->assign(collect([$admin, 'editor', $reviewer->id]))->to($user);

        $this->assertTrue($bouncer->is($user)->all($admin->id, $editor, 'reviewer'));

        $bouncer->retract(['admin', $editor])->from($user);

        $this->assertTrue($bouncer->is($user)->notAn($admin, 'editor'));
    }

    public function test_bouncer_can_give_and_remove_roles_for_multiple_users_at_once()
    {
        $user1 = User::create();
        $user2 = User::create();
        $bouncer = $this->bouncer($user1)->dontCache();

        $bouncer->assign(['admin', 'editor'])->to([$user1, $user2]);

        $this->assertTrue($bouncer->is($user1)->all('admin', 'editor'));
        $this->assertTrue($bouncer->is($user2)->an('admin', 'editor'));

        $bouncer->retract('admin')->from($user1);
        $bouncer->retract(collect(['admin', 'editor']))->from($user2);

        $this->assertTrue($bouncer->is($user1)->notAn('admin'));
        $this->assertTrue($bouncer->is($user1)->an('editor'));
        $this->assertTrue($bouncer->is($user1)->an('admin', 'editor'));
    }

    public function test_bouncer_can_ignore_duplicate_role_assignments()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->assign('admin')->to($user);
        $bouncer->assign('admin')->to($user);

        $this->assertCount(1, $user->roles);
    }

    public function test_bouncer_can_disallow_abilities_on_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->disallow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    public function test_bouncer_can_check_user_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertTrue($bouncer->is($user)->notA('moderator'));
        $this->assertTrue($bouncer->is($user)->notAn('editor'));
        $this->assertFalse($bouncer->is($user)->an('admin'));

        $bouncer = $this->bouncer($user = User::create());

        $bouncer->assign('moderator')->to($user);
        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->a('moderator'));
        $this->assertTrue($bouncer->is($user)->an('editor'));
        $this->assertFalse($bouncer->is($user)->notAn('editor'));
        $this->assertFalse($bouncer->is($user)->an('admin'));
    }

    public function test_bouncer_can_check_multiple_user_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertTrue($bouncer->is($user)->notAn('editor', 'moderator'));
        $this->assertTrue($bouncer->is($user)->notAn('admin', 'moderator'));

        $bouncer = $this->bouncer($user = User::create());
        $bouncer->assign('moderator')->to($user);
        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->a('subscriber', 'moderator'));
        $this->assertTrue($bouncer->is($user)->an('admin', 'editor'));
        $this->assertTrue($bouncer->is($user)->all('editor', 'moderator'));
        $this->assertFalse($bouncer->is($user)->notAn('editor', 'moderator'));
        $this->assertFalse($bouncer->is($user)->all('admin', 'moderator'));
    }

    public function test_bouncer_can_get_an_empty_role_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertInstanceOf(Role::class, $bouncer->role());
    }

    public function test_bouncer_can_fill_a_role_model()
    {
        $bouncer = $this->bouncer($user = User::create());
        $role = $bouncer->role(['name' => 'test-role']);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test-role', $role->name);
    }

    public function test_bouncer_can_get_an_empty_ability_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertInstanceOf(Ability::class, $bouncer->ability());
    }

    public function test_bouncer_can_fill_an_ability_model()
    {
        $bouncer = $this->bouncer($user = User::create());
        $ability = $bouncer->ability(['name' => 'test-ability']);

        $this->assertInstanceOf(Ability::class, $ability);
        $this->assertEquals('test-ability', $ability->name);
    }

    public function test_bouncer_can_allow_abilities_from_a_defined_callback()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->define('edit', function ($user, $account) {
            if ( ! $account instanceof Account) {
                return null;
            }

            return $user->id == $account->user_id;
        });

        $this->assertTrue($bouncer->can('edit', new Account(['user_id' => $user->id])));
        $this->assertFalse($bouncer->can('edit', new Account(['user_id' => 99])));
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
