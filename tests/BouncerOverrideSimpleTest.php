<?php


class BouncerOverrideSimpleTest extends BaseOverrideTestCase
{
    public function test_bouncer_can_give_and_remove_abilities()
    {
        $bouncer = $this->bouncer($user = MyUser::create());

        $bouncer->allow($user)->to('edit-site');

        $this->assertTrue($bouncer->allows('edit-site'));

        $bouncer->disallow($user)->to('edit-site');
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_bouncer_can_ignore_duplicate_ability_allowances()
    {
        $user1 = MyUser::create();
        $user2 = MyUser::create();

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
        $bouncer = $this->bouncer($user = MyUser::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->allows('edit-site'));

        $bouncer->retract('admin')->from($user);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_bouncer_can_ignore_duplicate_role_assignments()
    {
        $bouncer = $this->bouncer($user = MyUser::create());

        $bouncer->assign('admin')->to($user);
        $bouncer->assign('admin')->to($user);
    }

    public function test_bouncer_can_disallow_abilities_on_roles()
    {
        $bouncer = $this->bouncer($user = MyUser::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->disallow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->denies('edit-site'));
    }

    public function test_bouncer_can_check_user_roles()
    {
        $bouncer = $this->bouncer($user = MyUser::create());

        $bouncer->assign('moderator')->to($user);
        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->a('moderator'));
        $this->assertTrue($bouncer->is($user)->an('editor'));
        $this->assertFalse($bouncer->is($user)->an('admin'));
    }

    public function test_bouncer_can_check_multiple_user_roles()
    {
        $bouncer = $this->bouncer($user = MyUser::create());

        $bouncer->assign('moderator')->to($user);
        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->a('moderator', 'admin'));
        $this->assertTrue($bouncer->is($user)->an('editor', 'moderator'));
        $this->assertTrue($bouncer->is($user)->all('editor', 'moderator'));
        $this->assertFalse($bouncer->is($user)->all('admin', 'moderator'));
    }

    public function test_bouncer_can_get_an_empty_role_model()
    {
        $bouncer = $this->bouncer($user = MyUser::create());

        $this->assertInstanceOf(MyRole::class, $bouncer->role());
    }

    public function test_bouncer_can_fill_a_role_model()
    {
        $bouncer = $this->bouncer($user = MyUser::create());
        $role = $bouncer->role(['name' => 'test-role']);

        $this->assertInstanceOf(MyRole::class, $role);
        $this->assertEquals('test-role', $role->name);
    }

    public function test_bouncer_can_get_an_empty_ability_model()
    {
        $bouncer = $this->bouncer($user = MyUser::create());

        $this->assertInstanceOf(MyAbility::class, $bouncer->ability());
    }

    public function test_bouncer_can_fill_an_ability_model()
    {
        $bouncer = $this->bouncer($user = MyUser::create());
        $ability = $bouncer->ability(['name' => 'test-ability']);

        $this->assertInstanceOf(MyAbility::class, $ability);
        $this->assertEquals('test-ability', $ability->name);
    }
}
