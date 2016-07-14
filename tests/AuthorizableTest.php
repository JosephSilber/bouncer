<?php

use Silber\Bouncer\Database\Role;

class AuthorizableTest extends BaseTestCase
{
    public function testCheckingSimpleAbilitiesOnRoles()
    {
        $role = Role::create(['name' => 'admin']);

        $role->allow('scream');

        $this->assertTrue($role->can('scream'));
        $this->assertTrue($role->cant('shout'));
        $this->assertTrue($role->cannot('cry'));
    }

    public function testCheckingModelAbilitiesOnRoles()
    {
        $role = Role::create(['name' => 'admin']);

        $role->allow('create', User::class);

        $this->assertTrue($role->can('create', User::class));
        $this->assertTrue($role->cannot('create', Account::class));
        $this->assertTrue($role->cannot('update', User::class));
        $this->assertTrue($role->cannot('create'));
    }
}
