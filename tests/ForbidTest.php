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

        $this->assertTrue($bouncer->denies('edit-site'));

        $bouncer->unforbid($user)->to('edit-site');

        $this->assertTrue($bouncer->allows('edit-site'));
    }

    public function test_an_allowed_model_ability_is_not_granted_when_forbidden()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete', $user);
        $bouncer->forbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->denies('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->allows('delete', $user));
    }

    public function test_an_allowed_model_class_ability_is_not_granted_when_forbidden()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete', User::class);
        $bouncer->forbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->denies('delete', User::class));

        $bouncer->unforbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->allows('delete', User::class));
    }

    public function test_forbidding_a_single_model_forbids_even_with_allowed_model_class_ability()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete', User::class);
        $bouncer->forbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->denies('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->allows('delete', $user));
    }

    public function test_forbidding_a_single_model_does_not_forbid_other_models()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('delete', User::class);
        $bouncer->forbid($user1)->to('delete', $user2);

        $this->assertTrue($bouncer->allows('delete', $user1));
    }

    public function test_forbidding_a_model_class_forbids_individual_models()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to('delete', $user);
        $bouncer->forbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->denies('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->denies('delete', $user));
    }
}
