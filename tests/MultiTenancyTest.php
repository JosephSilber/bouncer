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

        $bouncer->scopeTo(1);

        $bouncer->allow('admin')->to('create', User::class);

        $this->assertEquals(1, $bouncer->ability()->query()->value('scope'));
        $this->assertEquals(1, $bouncer->role()->query()->value('scope'));
    }
}
