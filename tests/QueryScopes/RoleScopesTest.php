<?php

namespace Silber\Bouncer\Tests\QueryScopes;

use Silber\Bouncer\Tests\User;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Tests\BaseTestCase;

class RoleScopesTest extends BaseTestCase
{
    /**
     * @test
     */
    function roles_can_be_constrained_by_a_user()
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

    /**
     * @test
     */
    function roles_can_be_constrained_by_a_collection_of_users()
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

    /**
     * @test
     */
    function roles_can_be_constrained_by_a_model_name_and_keys()
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
