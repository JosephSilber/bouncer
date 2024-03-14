<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Silber\Bouncer\Database\Role;
use Workbench\App\Models\Account;
use Workbench\App\Models\User;

class AuthorizableTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function checking_simple_abilities_on_roles($provider)
    {
        $provider();

        $role = Role::create(['name' => 'admin']);

        $role->allow('scream');

        $this->assertTrue($role->can('scream'));
        $this->assertTrue($role->cant('shout'));
        $this->assertTrue($role->cannot('cry'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function checking_model_abilities_on_roles($provider)
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
