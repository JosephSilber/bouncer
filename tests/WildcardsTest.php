<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Models\Account;
use Workbench\App\Models\User;

class WildcardsTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function a_wildard_ability_allows_everything($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('*'));

        $bouncer->disallow($user)->to('*');

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->cannot('*'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function manage_allows_all_actions_on_a_model($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->toManage($user);

        $this->assertTrue($bouncer->can('*', $user));
        $this->assertTrue($bouncer->can('edit', $user));
        $this->assertTrue($bouncer->cannot('*', User::class));
        $this->assertTrue($bouncer->cannot('edit', User::class));

        $bouncer->disallow($user)->toManage($user);

        $this->assertTrue($bouncer->cannot('*', $user));
        $this->assertTrue($bouncer->cannot('edit', $user));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function manage_on_a_model_class_allows_all_actions_on_all_its_models($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->toManage(User::class);

        $this->assertTrue($bouncer->can('*', $user));
        $this->assertTrue($bouncer->can('edit', $user));
        $this->assertTrue($bouncer->can('*', User::class));
        $this->assertTrue($bouncer->can('edit', User::class));
        $this->assertTrue($bouncer->cannot('edit', Account::class));
        $this->assertTrue($bouncer->cannot('edit', Account::class));

        $bouncer->disallow($user)->toManage(User::class);

        $this->assertTrue($bouncer->cannot('*', $user));
        $this->assertTrue($bouncer->cannot('edit', $user));
        $this->assertTrue($bouncer->cannot('*', User::class));
        $this->assertTrue($bouncer->cannot('edit', User::class));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function always_allows_the_action_on_all_models($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->to('delete')->everything();

        $this->assertTrue($bouncer->can('delete', $user));
        $this->assertTrue($bouncer->cannot('update', $user));
        $this->assertTrue($bouncer->can('delete', User::class));
        $this->assertTrue($bouncer->can('delete', '*'));

        $bouncer->disallow($user)->to('delete')->everything();

        $this->assertTrue($bouncer->cannot('delete', $user));
        $this->assertTrue($bouncer->cannot('delete', User::class));
        $this->assertTrue($bouncer->cannot('delete', '*'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function everything_allows_everything($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->everything();

        $this->assertTrue($bouncer->can('*'));
        $this->assertTrue($bouncer->can('*', '*'));
        $this->assertTrue($bouncer->can('*', $user));
        $this->assertTrue($bouncer->can('*', User::class));
        $this->assertTrue($bouncer->can('ban', '*'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('ban', $user));
        $this->assertTrue($bouncer->can('ban', User::class));

        $bouncer->disallow($user)->everything();

        $this->assertTrue($bouncer->cannot('*'));
        $this->assertTrue($bouncer->cannot('*', '*'));
        $this->assertTrue($bouncer->cannot('*', $user));
        $this->assertTrue($bouncer->cannot('*', User::class));
        $this->assertTrue($bouncer->cannot('ban', '*'));
        $this->assertTrue($bouncer->cannot('ban-users'));
        $this->assertTrue($bouncer->cannot('ban', $user));
        $this->assertTrue($bouncer->cannot('ban', User::class));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function a_simple_wildard_ability_denies_model_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->cannot('edit', $user));
        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->cannot('*', $user));
        $this->assertTrue($bouncer->cannot('*', User::class));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function manage_denies_simple_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->toManage($user);

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->cannot('*'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function manage_on_a_model_class_denies_simple_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->toManage(User::class);

        $this->assertTrue($bouncer->cannot('*'));
        $this->assertTrue($bouncer->cannot('edit'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function always_denies_simple_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->to('delete')->everything();

        $this->assertTrue($bouncer->cannot('delete'));
    }
}
