<?php

use Silber\Bouncer\Database\Role;

class AuthorizableTest extends BaseTestCase
{
    public function testCheckingAbilitiesOnRole()
    {
        $role = Role::create(['name' => 'admin']);

        $role->allow('scream');

        $this->assertTrue($role->can('scream'));
        $this->assertTrue($role->cant('shout'));
        $this->assertTrue($role->cannot('cry'));
    }
}
