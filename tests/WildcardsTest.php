<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class WildcardsTest extends BaseTestCase
{
    public function test_a_wildard_ability_allows_everything()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('*'));

        $bouncer->disallow($user)->to('*');

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->cannot('*'));
    }

    public function test_manage_allows_all_actions_on_a_model()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toManage($user);

        $this->assertTrue($bouncer->can('*', $user));
        $this->assertTrue($bouncer->can('edit', $user));
        $this->assertTrue($bouncer->cannot('*', User::class));
        $this->assertTrue($bouncer->cannot('edit', User::class));

        $bouncer->disallow($user)->toManage($user);

        $this->assertTrue($bouncer->cannot('*', $user));
        $this->assertTrue($bouncer->cannot('edit', $user));
    }

    public function test_manage_on_a_model_class_allows_all_actions_on_all_its_models()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

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

    public function test_always_allows_the_action_on_all_models()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

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

    public function test_everything_allows_everything()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

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

    public function test_a_simple_wildard_ability_denies_model_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->cannot('edit', $user));
        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->cannot('*', $user));
        $this->assertTrue($bouncer->cannot('*', User::class));
    }

    public function test_manage_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage($user);

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->cannot('*'));
    }

    public function test_manage_on_a_model_class_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage(User::class);

        $this->assertTrue($bouncer->cannot('*'));
        $this->assertTrue($bouncer->cannot('edit'));
    }

    public function test_always_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete')->everything();

        $this->assertTrue($bouncer->cannot('delete'));
    }
}
