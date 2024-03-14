<?php

namespace Silber\Bouncer\Tests;

use Illuminate\Console\Application as Artisan;
use PHPUnit\Framework\Attributes\Test;
use Silber\Bouncer\Console\CleanCommand;
use Silber\Bouncer\Database\Ability;
use Workbench\App\Models\Account;
use Workbench\App\Models\User;

class CleanCommandTest extends BaseTestCase
{
    /**
     * Setup the world for the tests.
     */
    public function setUp(): void
    {
        Artisan::starting(
            fn ($artisan) => $artisan->resolveCommands(CleanCommand::class)
        );

        parent::setUp();
    }

    #[Test]
    public function the_orphaned_flag()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['access-dashboard', 'ban-users', 'throw-dishes']);
        $bouncer->disallow($user)->to(['access-dashboard', 'ban-users']);

        $this->assertEquals(3, Ability::query()->count());

        $this
            ->artisan('bouncer:clean --unassigned')
            ->expectsOutput('Deleted 2 unassigned abilities.');

        $this->assertEquals(1, Ability::query()->count());
        $this->assertTrue($bouncer->can('throw-dishes'));
    }

    #[Test]
    public function the_orphaned_flag_with_no_orphaned_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['access-dashboard', 'ban-users', 'throw-dishes']);

        $this->assertEquals(3, Ability::query()->count());

        $this
            ->artisan('bouncer:clean --unassigned')
            ->expectsOutput('No unassigned abilities.');

        $this->assertEquals(3, Ability::query()->count());
    }

    #[Test]
    public function the_missing_flag()
    {
        $bouncer = $this->bouncer($user1 = User::create())->dontCache();

        $account1 = Account::create();
        $account2 = Account::create();
        $user2 = User::create();

        $bouncer->allow($user1)->to('create', Account::class);
        $bouncer->allow($user1)->to('create', User::class);
        $bouncer->allow($user1)->to('update', $user1);
        $bouncer->allow($user1)->to('update', $user2);
        $bouncer->allow($user1)->to('update', $account1);
        $bouncer->allow($user1)->to('update', $account2);

        $account1->delete();
        $user2->delete();

        $this->assertEquals(6, Ability::query()->count());

        $this
            ->artisan('bouncer:clean --orphaned')
            ->expectsOutput('Deleted 2 orphaned abilities.');

        $this->assertEquals(4, Ability::query()->count());
        $this->assertTrue($bouncer->can('create', Account::class));
        $this->assertTrue($bouncer->can('create', User::class));
        $this->assertTrue($bouncer->can('update', $user1));
        $this->assertTrue($bouncer->can('update', $account2));
    }

    #[Test]
    public function no_flags()
    {
        $bouncer = $this->bouncer($user1 = User::create())->dontCache();

        $account1 = Account::create();
        $account2 = Account::create();
        $user2 = User::create();

        $bouncer->allow($user1)->to('update', $user1);
        $bouncer->allow($user1)->to('update', $user2);
        $bouncer->allow($user1)->to('update', $account1);
        $bouncer->allow($user1)->to('update', $account2);

        $bouncer->disallow($user1)->to('update', $user1);
        $account1->delete();

        $this->assertEquals(4, Ability::query()->count());

        $this
            ->artisan('bouncer:clean')
            ->expectsOutput('Deleted 1 unassigned ability.')
            ->expectsOutput('Deleted 1 orphaned ability.');

        $this->assertEquals(2, Ability::query()->count());
    }
}
