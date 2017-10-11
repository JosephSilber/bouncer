<?php

use Illuminate\Events\Dispatcher;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;

class MultiTenancyTest extends BaseTestCase
{
    /**
     * Reset any scopes that have been applied in a test.
     *
     * @return void
     */
    public function tearDown()
    {
        Models::scope()->reset();

        parent::tearDown();
    }

    public function test_creating_roles_and_abilities_automatically_scopes_them()
    {
        $bouncer = $this->bouncer();

        $bouncer->scope()->to(1);

        $bouncer->allow('admin')->to('create', User::class);

        $this->assertEquals(1, $bouncer->ability()->query()->value('scope'));
        $this->assertEquals(1, $bouncer->role()->query()->value('scope'));
    }

    public function test_relation_queries_are_properly_scoped()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->scope()->to(1);
        $bouncer->allow($user)->to('create', User::class);

        $bouncer->scope()->to(2);
        $bouncer->allow($user)->to('delete', User::class);

        $bouncer->scope()->to(1);
        $abilities = $user->abilities()->get();

        $this->assertCount(1, $abilities);
        $this->assertEquals(1, $abilities->first()->scope);
        $this->assertEquals('create', $abilities->first()->name);
        $this->assertTrue($bouncer->allows('create', User::class));
        $this->assertTrue($bouncer->denies('delete', User::class));

        $bouncer->scope()->to(2);
        $abilities = $user->abilities()->get();

        $this->assertCount(1, $abilities);
        $this->assertEquals(2, $abilities->first()->scope);
        $this->assertEquals('delete', $abilities->first()->name);
        $this->assertTrue($bouncer->allows('delete', User::class));
        $this->assertTrue($bouncer->denies('create', User::class));
    }

    public function test_relation_queries_can_be_scoped_exclusively()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->scope()->to(1)->onlyRelations();
        $bouncer->allow($user)->to('create', User::class);

        $bouncer->scope()->to(2)->onlyRelations();
        $bouncer->allow($user)->to('delete', User::class);

        $bouncer->scope()->to(1)->onlyRelations();
        $abilities = $user->abilities()->get();

        $this->assertCount(1, $abilities);
        $this->assertNull($abilities->first()->scope);
        $this->assertEquals('create', $abilities->first()->name);
        $this->assertTrue($bouncer->allows('create', User::class));
        $this->assertTrue($bouncer->denies('delete', User::class));

        $bouncer->scope()->to(2)->onlyRelations();
        $abilities = $user->abilities()->get();

        $this->assertCount(1, $abilities);
        $this->assertNull($abilities->first()->scope);
        $this->assertEquals('delete', $abilities->first()->name);
        $this->assertTrue($bouncer->allows('delete', User::class));
        $this->assertTrue($bouncer->denies('create', User::class));
    }
}
