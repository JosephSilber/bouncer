<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class BouncerSimpleTest extends BaseTestCase
{
    public function test_bouncer_can_give_and_remove_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('edit-site');

        $this->assertTrue($bouncer->allows('edit-site'));

        $bouncer->disallow($user)->to('edit-site');
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_bouncer_can_deny_access_if_set_to_work_exclusively()
    {
        $bouncer = $this->bouncer();

        $bouncer->getGate()->define('access-dashboard', function () {
            return true;
        });

        $this->assertTrue($bouncer->allows('access-dashboard'));

        $bouncer->exclusive();

        $this->assertTrue($bouncer->denies('access-dashboard'));
    }

    public function test_bouncer_can_ignore_duplicate_ability_allowances()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('ban-users');
        $bouncer->allow($user1)->to('ban-users');

        $bouncer->allow($user1)->to('ban', $user2);
        $bouncer->allow($user1)->to('ban', $user2);

        $bouncer->allow('admin')->to('ban-users');
        $bouncer->allow('admin')->to('ban-users');

        $bouncer->allow('admin')->to('ban', $user1);
        $bouncer->allow('admin')->to('ban', $user1);
    }

    public function test_bouncer_can_give_and_remove_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($user);

        $editor = $bouncer->role()->create(['name' => 'editor']);
        $bouncer->allow($editor)->to('edit-site');
        $bouncer->assign($editor)->to($user);

        $this->assertTrue($bouncer->allows('edit-site'));

        $bouncer->retract('admin')->from($user);
        $bouncer->retract($editor)->from($user);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_bouncer_can_ignore_duplicate_role_assignments()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->assign('admin')->to($user);
        $bouncer->assign('admin')->to($user);
    }

    public function test_bouncer_can_disallow_abilities_on_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->disallow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_bouncer_can_check_user_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertTrue($bouncer->is($user)->notA('moderator'));
        $this->assertTrue($bouncer->is($user)->notAn('editor'));
        $this->assertFalse($bouncer->is($user)->an('admin'));

        $bouncer = $this->bouncer($user = User::create());

        $bouncer->assign('moderator')->to($user);
        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->a('moderator'));
        $this->assertTrue($bouncer->is($user)->an('editor'));
        $this->assertFalse($bouncer->is($user)->notAn('editor'));
        $this->assertFalse($bouncer->is($user)->an('admin'));
    }

    public function test_bouncer_can_check_multiple_user_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertTrue($bouncer->is($user)->notAn('editor', 'moderator'));
        $this->assertTrue($bouncer->is($user)->notAn('admin', 'moderator'));

        $bouncer = $this->bouncer($user = User::create());
        $bouncer->assign('moderator')->to($user);
        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->a('subscriber', 'moderator'));
        $this->assertTrue($bouncer->is($user)->an('admin', 'editor'));
        $this->assertTrue($bouncer->is($user)->all('editor', 'moderator'));
        $this->assertFalse($bouncer->is($user)->notAn('editor', 'moderator'));
        $this->assertFalse($bouncer->is($user)->all('admin', 'moderator'));
    }

    public function test_bouncer_can_get_an_empty_role_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertInstanceOf(Role::class, $bouncer->role());
    }

    public function test_bouncer_can_fill_a_role_model()
    {
        $bouncer = $this->bouncer($user = User::create());
        $role = $bouncer->role(['name' => 'test-role']);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test-role', $role->name);
    }

    public function test_bouncer_can_get_an_empty_ability_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertInstanceOf(Ability::class, $bouncer->ability());
    }

    public function test_bouncer_can_fill_an_ability_model()
    {
        $bouncer = $this->bouncer($user = User::create());
        $ability = $bouncer->ability(['name' => 'test-ability']);

        $this->assertInstanceOf(Ability::class, $ability);
        $this->assertEquals('test-ability', $ability->name);
    }
}
