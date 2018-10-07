<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Bouncer;
use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;

class FactoryTest extends BaseTestCase
{
    /**
     * @test
     */
    function can_create_default_bouncer_instance()
    {
        $bouncer = Bouncer::create();

        $this->assertInstanceOf(Bouncer::class, $bouncer);
        $this->assertInstanceOf(Gate::class, $bouncer->getGate());
        $this->assertTrue($bouncer->usesCachedClipboard());
    }

    /**
     * @test
     */
    function can_create_bouncer_instance_for_given_the_user()
    {
        $bouncer = Bouncer::create($user = User::create());

        $bouncer->allow($user)->to('create-bouncers');

        $this->assertTrue($bouncer->can('create-bouncers'));
        $this->assertTrue($bouncer->cannot('delete-bouncers'));
    }

    /**
     * @test
     */
    function can_build_up_bouncer_with_the_given_user()
    {
        $bouncer = Bouncer::make()->withUser($user = User::create())->create();

        $bouncer->allow($user)->to('create-bouncers');

        $this->assertTrue($bouncer->can('create-bouncers'));
        $this->assertTrue($bouncer->cannot('delete-bouncers'));
    }

    /**
     * @test
     */
    function can_build_up_bouncer_with_the_given_gate()
    {
        $user = User::create();

        $gate = new Gate(new Container, function () use ($user) {
            return $user;
        });

        $bouncer = Bouncer::make()->withGate($gate)->create();

        $bouncer->allow($user)->to('create-bouncers');

        $this->assertTrue($bouncer->can('create-bouncers'));
        $this->assertTrue($bouncer->cannot('delete-bouncers'));
    }
}
