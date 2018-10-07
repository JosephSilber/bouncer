<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Role;

class AuthorizableTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function checking_simple_abilities_on_roles($provider)
    {
        $provider();

        $role = Role::create(['name' => 'admin']);

        $role->allow('scream');

        $this->assertTrue($role->can('scream'));
        $this->assertTrue($role->cant('shout'));
        $this->assertTrue($role->cannot('cry'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function checking_model_abilities_on_roles($provider)
    {
        $provider();

        $role = Role::create(['name' => 'admin']);

        $role->allow('create', User::class);

        $this->assertTrue($role->can('create', User::class));
        $this->assertTrue($role->cannot('create', Account::class));
        $this->assertTrue($role->cannot('update', User::class));
        $this->assertTrue($role->cannot('create'));
    }
}
