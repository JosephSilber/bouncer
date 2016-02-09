<?php

use Silber\Bouncer\Database\Role;

class RoleConstraintTest extends BaseTestCase
{
    public function test_roles_can_be_constrained_to_an_ability()
    {
        $bouncer = $this->bouncer();

        $bouncer->allow('admin')->to('administer-site');
        $bouncer->allow('editor')->to('view-dashboard');

        $roles = Role::whereCan('administer-site')->get();

        $this->assertCount(1, $roles);
        $this->assertEquals('admin', $roles->first()->name);
    }

    public function test_roles_can_be_constrained_to_a_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('moderator')->to('ban', $user);
        $bouncer->allow('admin')->to('ban');

        $roles = Role::whereCan('ban', $user)->get();

        $this->assertCount(1, $roles);
        $this->assertEquals('moderator', $roles->first()->name);

        $bouncer->allow('admin')->to('ban', User::class);

        $roles = Role::whereCan('ban', $user)->get();

        $this->assertCount(2, $roles);
    }

    public function test_roles_can_be_constrained_to_a_model_blanket_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('ban', User::class);
        $bouncer->allow('moderator')->to('ban', $user);

        $roles = Role::whereCan('ban', User::class)->get();

        $this->assertCount(1, $roles);
        $this->assertEquals('admin', $roles->first()->name);
    }

    public function test_roles_can_be_constrained_by_a_user()
    {
        $bouncer = $this->bouncer($user = User::create());

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'editor']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'subscriber']);

        $bouncer->assign('admin')->to($user);
        $bouncer->assign('manager')->to($user);

        $roles = Role::whereAssignedTo($user)->get();

        $this->assertCount(2, $roles);
        $this->assertTrue($roles->contains('name', 'admin'));
        $this->assertTrue($roles->contains('name', 'manager'));
        $this->assertFalse($roles->contains('name', 'editor'));
        $this->assertFalse($roles->contains('name', 'subscriber'));
    }

    public function test_roles_can_be_constrained_by_a_collection_of_users()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'editor']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'subscriber']);

        $bouncer->assign('editor')->to($user1);
        $bouncer->assign('manager')->to($user1);
        $bouncer->assign('subscriber')->to($user2);

        $roles = Role::whereAssignedTo(User::all())->get();

        $this->assertCount(3, $roles);
        $this->assertTrue($roles->contains('name', 'manager'));
        $this->assertTrue($roles->contains('name', 'editor'));
        $this->assertTrue($roles->contains('name', 'subscriber'));
        $this->assertFalse($roles->contains('name', 'admin'));
    }

    public function test_roles_can_be_constrained_by_a_model_name_and_keys()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'editor']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'subscriber']);

        $bouncer->assign('editor')->to($user1);
        $bouncer->assign('manager')->to($user1);
        $bouncer->assign('subscriber')->to($user2);

        $roles = Role::whereAssignedTo(User::class, User::all()->modelKeys())->get();

        $this->assertCount(3, $roles);
        $this->assertTrue($roles->contains('name', 'manager'));
        $this->assertTrue($roles->contains('name', 'editor'));
        $this->assertTrue($roles->contains('name', 'subscriber'));
        $this->assertFalse($roles->contains('name', 'admin'));
    }
}
