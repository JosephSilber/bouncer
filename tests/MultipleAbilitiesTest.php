<?php

class MultipleAbilitiesTest extends BaseTestCase
{

    public $report = false;

    public function setUp()
    {
        parent::setUp();

        if($this->report) {
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

    public function test_control_behaviour()
    {
        $user = User::create();

        $bouncer = $this->bouncer($user)->dontCache();

        $bouncer->allow($user)->to('edit');
        $bouncer->allow($user)->to('delete');

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));

        $bouncer->disallow($user)->to('edit');

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_control_behaviour_with_model_reference()
    {
        $user = User::create();

        $bouncer = $this->bouncer($user)->dontCache();

        $bouncer->allow($user)->to('edit', User::class);
        $bouncer->allow($user)->to('delete', User::class);

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));

        $bouncer->disallow($user)->to('edit', User::class);

        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));
    }

    public function test_multiple_abilties()
    {
        $user = User::create();

        $bouncer = $this->bouncer($user)->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));

        $bouncer->disallow($user)->to('edit');

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_multiple_abilties_with_model_reference()
    {
        $user = User::create();

        $bouncer = $this->bouncer($user)->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));

        $bouncer->disallow($user)->to('edit', User::class);

        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));
    }
}