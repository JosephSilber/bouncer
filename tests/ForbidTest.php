<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class ForbidTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function an_allowed_simple_ability_is_not_granted_when_forbidden($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to('edit-site');
        $bouncer->forbid($user)->to('edit-site');

        $this->assertTrue($bouncer->cannot('edit-site'));

        $bouncer->unforbid($user)->to('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function an_allowed_model_ability_is_not_granted_when_forbidden($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to('delete', $user);
        $bouncer->forbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->can('delete', $user));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function an_allowed_model_class_ability_is_not_granted_when_forbidden($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to('delete', User::class);
        $bouncer->forbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->cannot('delete', User::class));

        $bouncer->unforbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->can('delete', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_a_single_model_forbids_even_with_allowed_model_class_ability($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to('delete', User::class);
        $bouncer->forbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->can('delete', $user));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_a_single_model_does_not_forbid_other_models($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $bouncer->allow($user1)->to('delete', User::class);
        $bouncer->forbid($user1)->to('delete', $user2);

        $this->assertTrue($bouncer->can('delete', $user1));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_a_model_class_forbids_individual_models($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to('delete', $user);
        $bouncer->forbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->cannot('delete', $user));

        $bouncer->unforbid($user)->to('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_an_ability_through_a_role($provider)
    {
        list($bouncer, $user) = $provider();

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

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_an_ability_allowed_through_a_role($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow('admin')->to('delete', User::class);
        $bouncer->forbid($user)->to('delete', User::class);
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->cannot('delete', User::class));
        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_an_ability_when_everything_is_allowed($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->everything();
        $bouncer->forbid($user)->toManage(User::class);

        $this->assertTrue($bouncer->can('create', Account::class));
        $this->assertTrue($bouncer->cannot('create', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbid_an_ability_on_everything($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->everything();
        $bouncer->forbid($user)->to('delete')->everything();

        $this->assertTrue($bouncer->can('create', Account::class));
        $this->assertTrue($bouncer->cannot('delete', User::class));
        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_and_unforbidding_an_ability_for_everyone($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->everything();
        $bouncer->forbidEveryone()->to('delete', Account::class);

        $this->assertTrue($bouncer->can('delete', User::class));
        $this->assertTrue($bouncer->cannot('delete', Account::class));

        $bouncer->unforbidEveryone()->to('delete', Account::class);

        $this->assertTrue($bouncer->can('delete', Account::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_an_ability_stops_all_further_checks($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->define('sleep', function () {
            return true;
        });

        $this->assertTrue($bouncer->can('sleep'));

        $bouncer->forbid($user)->to('sleep');

        $this->assertTrue($bouncer->cannot('sleep'));
    }
}
