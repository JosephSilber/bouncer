<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class ForbidTest extends BaseTestCase
{
    public function test_an_allowed_simple_ability_is_not_granted_when_forbidden()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('edit-site');
        $bouncer->forbid($user)->to('edit-site');

        $this->assertTrue($bouncer->cannot('edit-site'));

        $bouncer->unforbid($user)->to('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));
    }

    public function test_an_allowed_model_ability_is_not_granted_when_forbidden()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete', $user);
        $bouncer->forbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->can('delete', $user));
    }

    public function test_an_allowed_model_class_ability_is_not_granted_when_forbidden()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete', User::class);
        $bouncer->forbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->cannot('delete', User::class));

        $bouncer->unforbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->can('delete', User::class));
    }

    public function test_forbidding_a_single_model_forbids_even_with_allowed_model_class_ability()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete', User::class);
        $bouncer->forbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->can('delete', $user));
    }

    public function test_forbidding_a_single_model_does_not_forbid_other_models()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('delete', User::class);
        $bouncer->forbid($user1)->to('delete', $user2);

        $this->assertTrue($bouncer->can('delete', $user1));
    }

    public function test_forbidding_a_model_class_forbids_individual_models()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete', $user);
        $bouncer->forbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->cannot('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    public function test_forbidding_an_through_a_role()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->forbid('admin')->to('delete', User::class);
        $bouncer->allow($user)->to('delete', User::class);
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->cannot('delete', User::class));
        $this->assertTrue($bouncer->cannot('delete', $user));

        $bouncer->unforbid('admin')->to('delete', User::class);

        $this->assertTrue($bouncer->can('delete', User::class));
        $this->assertTrue($bouncer->can('delete', $user));

        $bouncer->forbid('admin')->to('delete', $user);

        $this->assertTrue($bouncer->can('delete', User::class));
        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    public function test_forbidding_an_ability_allowed_through_a_role()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow('admin')->to('delete', User::class);
        $bouncer->forbid($user)->to('delete', User::class);
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->cannot('delete', User::class));
        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    public function test_forbidding_an_ability_when_everything_is_allowed()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->everything();
        $bouncer->forbid($user)->toManage(User::class);

        $this->assertTrue($bouncer->can('create', Account::class));
        $this->assertTrue($bouncer->cannot('create', User::class));
    }

    public function test_forbid_an_ability_on_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->everything();
        $bouncer->forbid($user)->to('delete')->everything();

        $this->assertTrue($bouncer->can('create', Account::class));
        $this->assertTrue($bouncer->cannot('delete', User::class));
        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    public function test_forbidding_an_ability_stops_all_further_checks()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->define('sleep', function () {
            return true;
        });

        $this->assertTrue($bouncer->can('sleep'));

        $bouncer->forbid($user)->to('sleep');

        $this->assertTrue($bouncer->cannot('sleep'));
    }
}
