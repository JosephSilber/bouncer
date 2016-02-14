<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class WildcardsTest extends BaseTestCase
{
    public function test_a_wildard_ability_allows_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->allows('edit-site'));
        $this->assertTrue($bouncer->allows('*'));

        $bouncer->disallow($user)->to('*');
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit-site'));
        $this->assertTrue($bouncer->denies('*'));
    }

    public function test_a_model_wildard_ability_allows_all_actions_on_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*', $user);

        $this->assertTrue($bouncer->allows('*', $user));
        $this->assertTrue($bouncer->allows('edit', $user));
        $this->assertTrue($bouncer->denies('*', User::class));
        $this->assertTrue($bouncer->denies('edit', User::class));

        $bouncer->disallow($user)->to('*', $user);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('*', $user));
        $this->assertTrue($bouncer->denies('edit', $user));
    }

    public function test_a_model_blanket_wildard_ability_allows_all_actions_on_all_its_models()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*', User::class);

        $this->assertTrue($bouncer->allows('*', $user));
        $this->assertTrue($bouncer->allows('edit', $user));
        $this->assertTrue($bouncer->allows('*', User::class));
        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->denies('edit', Account::class));
        $this->assertTrue($bouncer->denies('edit', Account::class));

        $bouncer->disallow($user)->to('*', User::class);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('*', $user));
        $this->assertTrue($bouncer->denies('edit', $user));
        $this->assertTrue($bouncer->denies('*', User::class));
        $this->assertTrue($bouncer->denies('edit', User::class));
    }

    public function test_an_action_with_a_wildcard_allows_the_action_on_all_models()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete', '*');

        $this->assertTrue($bouncer->allows('delete', $user));
        $this->assertTrue($bouncer->allows('delete', User::class));
        $this->assertTrue($bouncer->allows('delete', '*'));

        $bouncer->disallow($user)->to('delete', '*');
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('delete', $user));
        $this->assertTrue($bouncer->denies('delete', User::class));
        $this->assertTrue($bouncer->denies('delete', '*'));
    }

    public function test_double_wildcard_allows_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*', '*');

        $this->assertTrue($bouncer->allows('*'));
        $this->assertTrue($bouncer->allows('*', '*'));
        $this->assertTrue($bouncer->allows('*', $user));
        $this->assertTrue($bouncer->allows('*', User::class));
        $this->assertTrue($bouncer->allows('ban', '*'));
        $this->assertTrue($bouncer->allows('ban-users'));
        $this->assertTrue($bouncer->allows('ban', $user));
        $this->assertTrue($bouncer->allows('ban', User::class));

        $bouncer->disallow($user)->to('*', '*');
        $this->clipboard->refresh();

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

    public function test_a_model_wildard_ability_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*', $user);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('*'));
    }

    public function test_a_model_blanket_wildard_ability_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*', User::class);

        $this->assertTrue($bouncer->denies('*'));
        $this->assertTrue($bouncer->denies('edit'));
    }

    public function test_an_action_with_a_wildcard_denies_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete', '*');

        $this->assertTrue($bouncer->denies('delete'));
    }
}
