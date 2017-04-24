<?php

class MultipleAbilitiesTest extends BaseTestCase
{

    /**
     * When set to true, queries are counted and reported for tests in this file.
     * @var boolean
     */
    public $report = false;

    public function setUp()
    {
        parent::setUp();

        if ($this->report) {
            $this->db()->connection()->enableQueryLog();
        }
    }

    public function tearDown()
    {
        if($this->report) {
            var_dump($this->getName().': '.count($this->db()->connection()->getQueryLog()));
        }

        parent::tearDown();
    }

    public function test_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));

        $bouncer->disallow($user)->to('edit');

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_multiple_abilties_with_model_reference()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));

        $bouncer->disallow($user)->to('edit', User::class);

        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));
    }

    public function test_combine_techniques()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();
        $user2 = User::create();

        $bouncer->allow($user)->to([
            'edit' => User::class,
            'delete' => $user2,
        ]);

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', $user2));
    }

    public function test_allow_multiple_abilties()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_disallow_multiple_abilties()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->disallow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_forbid_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->forbid($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_unforbid_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->forbid($user)->to(['edit', 'delete']);
        
        $this->assertTrue($bouncer->denies('edit'));

        $bouncer->unforbid($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_forbid_multiple_abilities_with_model_reference()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);
        $bouncer->forbid($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->denies('delete', User::class));

        $bouncer->unforbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->allows('delete', User::class));
        $this->assertFalse($bouncer->allows('edit', User::class));
    }

    public function test_multiple_abilities_for_roles()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow('admin')->to(['edit', 'delete']);
        $user->assign('admin');

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));

        $user->retract('admin');

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_user_trait_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $user->allow(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));

        $user->disallow('delete');

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_user_with_multiple_roles()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow('supermod')->to('delete');
        $bouncer->allow('moderator')->to('edit');
        $user->assign(['supermod', 'moderator']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));

        $user->retract(['supermod', 'moderator']);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_assign_retract_multiple_users()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer()->dontCache();

        $bouncer->assign('admin')->to([$user1, $user2]);

        $this->assertTrue($bouncer->is($user1)->an('admin'));
        $this->assertTrue($bouncer->is($user2)->an('admin'));

        $bouncer->retract('admin')->from([$user1, $user2]);

        $this->assertTrue($bouncer->is($user1)->notAn('admin'));
        $this->assertTrue($bouncer->is($user2)->notAn('admin'));
    }
}