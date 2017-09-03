<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class WildcardsTest extends BaseTestCase
{
    public function test_a_wildard_ability_allows_everything()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->allows('edit-site'));
        $this->assertTrue($bouncer->allows('*'));

        $bouncer->disallow($user)->to('*');

        $this->assertTrue($bouncer->denies('edit-site'));
        $this->assertTrue($bouncer->denies('*'));
    }

    public function test_manage_allows_all_actions_on_a_model()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toManage($user);

        $this->assertTrue($bouncer->allows('*', $user));
        $this->assertTrue($bouncer->allows('edit', $user));
        $this->assertTrue($bouncer->denies('*', User::class));
        $this->assertTrue($bouncer->denies('edit', User::class));

        $bouncer->disallow($user)->toManage($user);

        $this->assertTrue($bouncer->denies('*', $user));
        $this->assertTrue($bouncer->denies('edit', $user));
    }

    public function test_manage_on_a_model_class_allows_all_actions_on_all_its_models()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toManage(User::class);

        $this->assertTrue($bouncer->allows('*', $user));
        $this->assertTrue($bouncer->allows('edit', $user));
        $this->assertTrue($bouncer->allows('*', User::class));
        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->denies('edit', Account::class));
        $this->assertTrue($bouncer->denies('edit', Account::class));

        $bouncer->disallow($user)->toManage(User::class);

        $this->assertTrue($bouncer->denies('*', $user));
        $this->assertTrue($bouncer->denies('edit', $user));
        $this->assertTrue($bouncer->denies('*', User::class));
        $this->assertTrue($bouncer->denies('edit', User::class));
    }

    public function test_always_allows_the_action_on_all_models()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete')->everything();

        $this->assertTrue($bouncer->allows('delete', $user));
        $this->assertTrue($bouncer->denies('update', $user));
        $this->assertTrue($bouncer->allows('delete', User::class));
        $this->assertTrue($bouncer->allows('delete', '*'));

        $bouncer->disallow($user)->to('delete')->everything();

        $this->assertTrue($bouncer->denies('delete', $user));
        $this->assertTrue($bouncer->denies('delete', User::class));
        $this->assertTrue($bouncer->denies('delete', '*'));
    }

    public function test_everything_allows_everything()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->everything();

        $this->assertTrue($bouncer->allows('*'));
        $this->assertTrue($bouncer->allows('*', '*'));
        $this->assertTrue($bouncer->allows('*', $user));
        $this->assertTrue($bouncer->allows('*', User::class));
        $this->assertTrue($bouncer->allows('ban', '*'));
        $this->assertTrue($bouncer->allows('ban-users'));
        $this->assertTrue($bouncer->allows('ban', $user));
        $this->assertTrue($bouncer->allows('ban', User::class));

        $bouncer->disallow($user)->everything();

        $this->assertTrue($bouncer->denies('*'));
        $this->assertTrue($bouncer->denies('*', '*'));
        $this->assertTrue($bouncer->denies('*', $user));
        $this->assertTrue($bouncer->denies('*', User::class));
        $this->assertTrue($bouncer->denies('ban', '*'));
        $this->assertTrue($bouncer->denies('ban-users'));
        $this->assertTrue($bouncer->denies('ban', $user));
        $this->assertTrue($bouncer->denies('ban', User::class));
    }

    public function test_a_simple_wildard_ability_denies_model_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->denies('edit', $user));
        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->denies('*', $user));
        $this->assertTrue($bouncer->denies('*', User::class));
    }

    public function test_manage_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage($user);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('*'));
    }

    public function test_manage_on_a_model_class_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage(User::class);

        $this->assertTrue($bouncer->denies('*'));
        $this->assertTrue($bouncer->denies('edit'));
    }

    public function test_always_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete')->everything();

        $this->assertTrue($bouncer->denies('delete'));
    }
}
