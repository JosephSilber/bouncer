<?php

use Silber\Bouncer\Database\Role;

class RoleCanConstraintTest extends BaseTestCase
{
    public function test_role_can_be_constrained_to_an_ability()
    {
        $bouncer = $this->bouncer();

        $bouncer->allow('admin')->to('administer-site');
        $bouncer->allow('editor')->to('view-dashboard');

        $roles = Role::whereCan('administer-site')->get();

        $this->assertCount(1, $roles);
        $this->assertEquals('admin', $roles->first()->name);
    }

    public function test_users_can_be_constrained_to_a_model_ability()
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

    public function test_users_can_be_constrained_to_a_model_blanket_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('ban', User::class);
        $bouncer->allow('moderator')->to('ban', $user);

        $roles = Role::whereCan('ban', User::class)->get();

        $this->assertCount(1, $roles);
        $this->assertEquals('admin', $roles->first()->name);
    }
}
