<?php

class UserCanConstraintTest extends BaseTestCase
{
    public function test_users_can_be_constrained_to_an_ability()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->allow('view-dashboard');

        $users = User::whereCan('view-dashboard')->get();

        $this->assertCount(1, $users);

        $this->assertEquals('Joseph', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_an_ability_granted_through_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $bouncer = $this->bouncer();

        $bouncer->allow('admin')->to('ban-users');
        $bouncer->assign('admin')->to($user1);

        $users = User::whereCan('ban-users')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_a_model_ability()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->allow('ban', $user2);
        $user2->allow('ban');

        $users = User::whereCan('ban', $user2)->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);

        $user2->allow('ban', User::class);

        $users = User::whereCan('ban', $user2)->get();

        $this->assertCount(2, $users);
    }

    public function test_users_can_be_constrained_to_a_model_ability_granted_through_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $bouncer = $this->bouncer();

        $bouncer->allow('moderator')->to('ban', $user2);
        $bouncer->assign('moderator')->to($user1);

        $users = User::whereCan('ban', $user2)->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);

        $bouncer->allow('admin')->to('ban', User::class);
        $bouncer->assign('admin')->to($user2);

        $users = User::whereCan('ban', $user2)->get();

        $this->assertCount(2, $users);
    }

    public function test_users_can_be_constrained_to_a_model_blanket_ability()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $user1->allow('ban', User::class);
        $user2->allow('ban', $user1);

        $users = User::whereCan('ban', User::class)->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);
    }

    public function test_users_can_be_constrained_to_a_model_blanket_ability_granted_through_a_role()
    {
        $user1 = User::create(['name' => 'Joseph']);
        $user2 = User::create(['name' => 'Silber']);

        $bouncer = $this->bouncer();

        $bouncer->allow('admin')->to('ban', User::class);
        $bouncer->assign('admin')->to($user1);

        $users = User::whereCan('ban', User::class)->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Joseph', $users->first()->name);
    }
}
