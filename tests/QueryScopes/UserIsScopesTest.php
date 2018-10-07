<?php

namespace Silber\Bouncer\Tests\QueryScopes;

use Silber\Bouncer\Tests\User;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Tests\BaseTestCase;

class UserIsScopesTest extends BaseTestCase
{
    /**
     * @test
     */
    function users_can_be_constrained_to_having_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->assign('reader');
        $user2->assign('subscriber');

        $users = User::whereIs('reader')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);
    }

    /**
     * @test
     */
    function users_can_be_constrained_to_having_one_of_many_roles()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->assign('reader');
        $user2->assign('subscriber');

        $users = User::whereIs('admin', 'subscriber')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Silber', $users->first()->name);
    }

    /**
     * @test
     */
    function users_can_be_constrained_to_having_all_provided_roles()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->assign('reader')->assign('subscriber');
        $user2->assign('subscriber');

        $users = User::whereIsAll('subscriber', 'reader')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);
    }

    /**
     * @test
     */
    function users_can_be_constrained_to_not_having_a_role()
    {
        $user1 = User::create();
        $user2 = User::create();
        $user3 = User::create();

        $user1->assign('admin');
        $user2->assign('editor');
        $user3->assign('subscriber');

        $users = User::whereIsNot('admin')->get();

        $this->assertCount(2, $users);
        $this->assertFalse($users->contains($user1));
        $this->assertTrue($users->contains($user2));
        $this->assertTrue($users->contains($user3));
    }

    /**
     * @test
     */
    function users_can_be_constrained_to_not_having_any_of_the_given_roles()
    {
        $user1 = User::create();
        $user2 = User::create();
        $user3 = User::create();

        $user1->assign('admin');
        $user2->assign('editor');
        $user3->assign('subscriber');

        $users = User::whereIsNot('superadmin', 'editor', 'subscriber')->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users->contains($user1));
        $this->assertFalse($users->contains($user2));
        $this->assertFalse($users->contains($user3));
    }
}
