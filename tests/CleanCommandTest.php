<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Console\CleanCommand;

class CleanCommandTest extends BaseTestCase
{
    use Concerns\TestsConsoleCommands;

    /**
     * @test
     */
    function the_orphaned_flag()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['access-dashboard', 'ban-users', 'throw-dishes']);
        $bouncer->disallow($user)->to(['access-dashboard', 'ban-users']);

        $this->assertEquals(3, Ability::query()->count());

        $this->clean(['--unassigned' => true], '<info>Deleted 2 unassigned abilities.</info>');

        $this->assertEquals(1, Ability::query()->count());
        $this->assertTrue($bouncer->can('throw-dishes'));
    }

    /**
     * @test
     */
    function the_orphaned_flag_with_no_orphaned_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['access-dashboard', 'ban-users', 'throw-dishes']);

        $this->assertEquals(3, Ability::query()->count());

        $this->clean(['--unassigned' => true], '<info>No unassigned abilities.</info>');

        $this->assertEquals(3, Ability::query()->count());
    }

    /**
     * @test
     */
    function the_missing_flag()
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

        $this->clean(['--orphaned' => true], '<info>Deleted 2 orphaned abilities.</info>');

        $this->assertEquals(4, Ability::query()->count());
        $this->assertTrue($bouncer->can('create', Account::class));
        $this->assertTrue($bouncer->can('create', User::class));
        $this->assertTrue($bouncer->can('update', $user1));
        $this->assertTrue($bouncer->can('update', $account2));
    }

    /**
     * @test
     */
    function no_flags()
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

        $this->clean([], [
            '<info>Deleted 1 unassigned ability.</info>',
            '<info>Deleted 1 orphaned ability.</info>'
        ]);

        $this->assertEquals(2, Ability::query()->count());
    }

    /**
     * Run the clean command, and see the given message in the output.
     *
     * @param  array  $parameters
     * @param  string|array  $message
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function clean(array $parameters = [], $message)
    {
        return $this->runCommand(
            new CleanCommand, $parameters, $this->predictOutputMessage($message)
        );
    }
}
