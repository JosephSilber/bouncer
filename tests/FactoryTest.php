<?php

use Silber\Bouncer\Bouncer;
use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;

class FactoryTest extends BaseTestCase
{
    public function testCanCreateDefaultBouncerInstance()
    {
        $bouncer = Bouncer::create();

        $this->assertInstanceOf(Bouncer::class, $bouncer);
        $this->assertInstanceOf(Gate::class, $bouncer->getGate());
        $this->assertTrue($bouncer->usesCachedClipboard());
    }

    public function testCanCreateBouncerInstanceForGivenTheUser()
    {
        $bouncer = Bouncer::create($user = User::create());

        $bouncer->allow($user)->to('create-bouncers');

        $this->assertTrue($bouncer->allows('create-bouncers'));
        $this->assertTrue($bouncer->denies('delete-bouncers'));
    }

    public function testCanBuildUpBouncerWithTheGivenUser()
    {
        $bouncer = Bouncer::make()->withUser($user = User::create())->create();

        $bouncer->allow($user)->to('create-bouncers');

        $this->assertTrue($bouncer->allows('create-bouncers'));
        $this->assertTrue($bouncer->denies('delete-bouncers'));
    }

    public function testCanBuildUpBouncerWithTheGivenGate()
    {
        $user = User::create();

        $gate = new Gate(new Container, function () use ($user) {
            return $user;
        });

        $bouncer = Bouncer::make()->withGate($gate)->create();

        $bouncer->allow($user)->to('create-bouncers');

        $this->assertTrue($bouncer->allows('create-bouncers'));
        $this->assertTrue($bouncer->denies('delete-bouncers'));
    }



}
