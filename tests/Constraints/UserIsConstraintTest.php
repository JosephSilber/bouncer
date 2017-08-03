<?php

use Silber\Bouncer\Database\Role;

class UserIsConstraintTest extends BaseTestCase
{
    public function test_users_can_be_constrained_to_having_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->assign('reader');
        $user2->assign('subscriber');

        $users = User::whereIs('reader')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_having_one_of_many_roles()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->assign('reader');
        $user2->assign('subscriber');

        $users = User::whereIs('admin', 'subscriber')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Silber', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_having_all_provided_roles()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->assign('reader')->assign('subscriber');
        $user2->assign('subscriber');

        $users = User::whereIsAll('subscriber', 'reader')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_doesnt_having_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);
        $user3 = User::create(['name' => 'john']);

        $user1->assign('reader');
        $user2->assign('subscriber');

        $users = User::whereIsNot('reader')->get();

        $this->assertCount(2, $users);
        $this->assertFalse($users->contains('name', 'Joseph'));
        $this->assertTrue($users->contains('name', 'Silber'));
        $this->assertTrue($users->contains('name', 'john'));
    }

    public function test_users_can_be_constrained_to_doesnt_having_one_of_many_roles()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);
        $user3 = User::create(['name' => 'john']);
        $user4 = User::create(['name' => 'doe']);

        $user1->assign('reader');
        $user2->assign('subscriber');
        $user3->assign('editor');

        $users = User::whereIsNot('admin', 'reader', 'subscriber')->get();

        $this->assertCount(2, $users);
        $this->assertFalse($users->contains('name', 'Joseph'));
        $this->assertFalse($users->contains('name', 'Silber'));
        $this->assertTrue($users->contains('name', 'john'));
        $this->assertTrue($users->contains('name', 'doe'));
    }

    public function test_users_can_be_constrained_to_doesnt_having_all_provided_roles()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);
        $user3 = User::create(['name' => 'john']);
        $user4 = User::create(['name' => 'doe']);

        $user1->assign('reader')->assign('subscriber');
        $user2->assign('subscriber');
        $user3->assign('editor');

        $users = User::whereIsNotAll('subscriber', 'reader')->get();

        $this->assertCount(3, $users);
        $this->assertFalse($users->contains('name', 'Joseph'));
        $this->assertTrue($users->contains('name', 'Silber'));
        $this->assertTrue($users->contains('name', 'john'));
        $this->assertTrue($users->contains('name', 'doe'));
    }
}
