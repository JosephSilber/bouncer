<?php

class UserCannotConstraintTest extends BaseTestCase
{
    public function test_users_can_be_constrained_to_not_having_an_ability()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->allow('view-dashboard');

        $users = User::whereCannot('view-dashboard')->get();

        $this->assertCount(1, $users);

        $this->assertEquals('Silber', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_not_having_an_ability_granted_through_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $bouncer = $this->bouncer($user1);

        $bouncer->allow('admin')->to('ban-users');
        $bouncer->assign('admin')->to($user1);

        $users = User::whereCannot('ban-users')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Silber', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_not_having_a_model_ability()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->allow('ban', $user2);
        $user2->allow('ban');

        $users = User::whereCannot('ban', $user2)->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Silber', $users->first()->name);

        $user2->allow('ban', User::class);

        $users = User::whereCannot('ban', $user2)->get();

        $this->assertCount(0, $users);
    }

    public function test_users_can_be_constrained_to_not_having_a_model_ability_granted_through_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $bouncer = $this->bouncer($user1);

        $bouncer->allow('moderator')->to('ban', $user2);
        $bouncer->assign('moderator')->to($user1);

        $users = User::whereCannot('ban', $user2)->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Silber', $users->first()->name);

        $bouncer->allow('admin')->to('ban', User::class);
        $bouncer->assign('admin')->to($user2);

        $users = User::whereCannot('ban', $user2)->get();

        $this->assertCount(0, $users);
    }

    public function test_users_can_be_constrained_to_not_having_a_model_blanket_ability()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->allow('ban', User::class);
        $user2->allow('ban', $user1);

        $users = User::whereCannot('ban', User::class)->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Silber', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_not_having_a_model_blanket_ability_granted_through_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $bouncer = $this->bouncer($user1);

        $bouncer->allow('admin')->to('ban', User::class);
        $bouncer->assign('admin')->to($user1);

        $users = User::whereCannot('ban', User::class)->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Silber', $users->first()->name);
    }
}
