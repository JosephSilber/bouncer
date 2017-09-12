<?php

use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Database\Models;

class OverrideUserModelTest extends BaseTestCase
{
    public function testModelsUsesAppUserByDefault()
    {
        $this->assertEquals('App\User', Models::getUserClass());
    }

    public function testCanOverrideModelsUserClassWithString()
    {
        Models::setUserClass(AnotherUserClass::class);

        $this->assertEquals('AnotherUserClass', Models::getUserClass());
    }

    public function testCanOverrideModelsUserClassWithObject()
    {
        Models::setUserClass(new AnotherUserClass);

        $this->assertEquals('AnotherUserClass', Models::getUserClass());
    }

    public function testCanOverrideModelsUserClassFromBouncerWithString()
    {
        Bouncer::setUserClass(AnotherUserClass::class);

        $this->assertEquals('AnotherUserClass', Models::getUserClass());
    }

    public function testCanOverrideModelsUserClassFromBouncerWithObject()
    {
        Bouncer::setUserClass(new AnotherUserClass);

        $this->assertEquals('AnotherUserClass', Models::getUserClass());
    }
}

class AnotherUserClass
{}
